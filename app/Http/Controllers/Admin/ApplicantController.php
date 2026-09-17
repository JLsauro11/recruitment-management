<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobVacancy;
use App\Services\AssessmentInsightService;
use App\Services\RecruitmentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ApplicantController extends Controller
{
    public function index(): View
    {
        return view('admin.applicants.index');
    }

    public function data(): JsonResponse
    {
        $applications = Application::query()
            ->with([
                'applicant',
                'vacancy.position.department',
                'interviews' => fn ($query) => $query->latest('scheduled_at'),
                'formSubmissions.answers.field',
            ])
            ->latest('applied_at')
            ->get()
            ->map(function (Application $application) {
                $applicant = $application->applicant;

                $fullName = collect([
                    $applicant?->first_name,
                    $applicant?->middle_name,
                    $applicant?->last_name,
                ])->filter()->implode(' ');

                $latestInterview = $application->interviews->first();

                $resumeAnswer = $application->formSubmissions
                    ->flatMap(fn ($submission) => $submission->answers)
                    ->first(function ($answer) {
                        $label = strtolower(trim($answer->field?->label ?? ''));

                        return str_contains($label, 'resume')
                            || $label === 'cv'
                            || str_contains($label, 'curriculum vitae');
                    });

                $resumeUrl = $this->publicFileUrl($resumeAnswer?->value);

                return [
                    'id' => $application->id,
                    'reference_no' => $application->reference_no,
                    'applicant_name' => $fullName ?: 'N/A',
                    'email' => $applicant?->email ?? 'N/A',
                    'position' => $application->vacancy?->position?->name
                        ?? $application->vacancy?->title
                        ?? 'N/A',
                    'vacancy_title' => $application->vacancy?->title ?? 'N/A',
                    'department' => $application->vacancy?->position?->department?->name ?? 'N/A',
                    'applied_at' => optional($application->applied_at)->format('M d, Y'),
                    'status' => $application->status,
                    'forms_count' => $application->formSubmissions->count(),
                    'interviews_count' => $application->interviews->count(),
                    'latest_interview' => $latestInterview ? [
                        'type' => $latestInterview->type,
                        'status' => $latestInterview->status,
                        'scheduled_at' => optional($latestInterview->scheduled_at)
                            ->format('M d, Y g:i A'),
                    ] : null,
                    'resume_url' => $resumeUrl,
                    'has_resume' => filled($resumeUrl),
                ];
            });

        return response()->json([
            'data' => $applications,
        ]);
    }

    public function show(Application $application): JsonResponse
    {
        return response()->json(
            $application->load([
                'applicant',
                'vacancy.position.department',
                'interviews',
                'formSubmissions.template',
                'statusHistories',
            ])
        );
    }

    /**
     * Return the applicant's submitted employment information in a format
     * that can be edited from the Applicants page.
     */
    public function edit(Application $application): JsonResponse
    {
        $application->load([
            'applicant',
            'vacancy.position.department',
            'formSubmissions' => fn ($query) => $query
                ->with(['template', 'answers.field'])
                ->orderBy('id'),
        ]);

        $forms = $application->formSubmissions
            ->sortBy(fn ($submission) => sprintf(
                '%010d-%010d',
                $submission->template?->sort_order ?? PHP_INT_MAX,
                $submission->template?->id ?? PHP_INT_MAX
            ))
            ->values()
            ->map(function ($submission) {
                $answers = $submission->answers
                    ->filter(fn ($answer) => $answer->field !== null)
                    ->sortBy(fn ($answer) => sprintf(
                        '%010d-%010d',
                        $answer->field?->sort_order ?? PHP_INT_MAX,
                        $answer->field?->id ?? PHP_INT_MAX
                    ));

                $sections = $answers
                    ->groupBy(fn ($answer) => filled($answer->field?->section)
                        ? $answer->field->section
                        : 'General Information')
                    ->map(function ($sectionAnswers, $sectionName) {
                        return [
                            'name' => $sectionName,
                            'fields' => $sectionAnswers->map(function ($answer) {
                                $field = $answer->field;
                                $value = $answer->value;

                                if ($field->field_type === 'checkbox') {
                                    $decoded = json_decode((string) $value, true);
                                    $value = is_array($decoded) ? array_values($decoded) : [];
                                }

                                return [
                                    'id' => $field->id,
                                    'label' => $field->label,
                                    'field_key' => $field->field_key,
                                    'field_type' => $field->field_type,
                                    'options' => array_values($field->options ?? []),
                                    'placeholder' => $field->placeholder,
                                    'is_required' => (bool) $field->is_required,
                                    'width' => max(1, min(12, (int) ($field->width ?: 12))),
                                    'value' => $field->field_type === 'file' ? null : $value,
                                    'current_file_url' => $field->field_type === 'file'
                                        ? $this->publicFileUrl($answer->value)
                                        : null,
                                    'current_file_name' => $field->field_type === 'file'
                                        ? $this->fileDisplayName($answer->value)
                                        : null,
                                ];
                            })->values(),
                        ];
                    })
                    ->values();

                return [
                    'id' => $submission->id,
                    'template_name' => $submission->template?->name ?? 'Submitted Form',
                    'template_type' => $submission->template?->type,
                    'sections' => $sections,
                ];
            });

        $vacancies = JobVacancy::query()
            ->with('position.department')
            ->orderBy('title')
            ->get()
            ->map(function (JobVacancy $vacancy) {
                $department = $vacancy->position?->department?->name;
                $position = $vacancy->position?->name;

                $details = collect([$position, $department])
                    ->filter()
                    ->implode(' - ');

                return [
                    'id' => $vacancy->id,
                    'title' => $vacancy->title,
                    'status' => $vacancy->effective_status,
                    'label' => $vacancy->title
                        . ($details ? ' | ' . $details : '')
                        . ' (' . $vacancy->effective_status . ')',
                ];
            })
            ->values();

        return response()->json([
            'application' => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'job_vacancy_id' => $application->job_vacancy_id,
                'status' => $application->status,
                'applicant_name' => $application->applicant?->full_name ?? 'Applicant',
            ],
            'vacancies' => $vacancies,
            'forms' => $forms,
        ]);
    }

    /**
     * Update the applicant's submitted information and keep the normalized
     * applicant profile in sync with the edited form values.
     */
    public function update(Request $request, Application $application): JsonResponse
    {
        $application->load([
            'applicant',
            'formSubmissions.answers.field',
        ]);

        $applicant = $application->applicant;
        abort_if(!$applicant, 404, 'Applicant profile not found.');

        $answers = $application->formSubmissions
            ->flatMap(fn ($submission) => $submission->answers)
            ->filter(fn ($answer) => $answer->field !== null)
            ->values();

        abort_if($answers->isEmpty(), 404, 'No submitted applicant information was found.');

        $posted = $request->input('answers', []);
        if (is_array($posted)) {
            $existing = [];
            foreach ($answers as $answer) {
                if ($answer->field->field_type === 'file') {
                    continue;
                }
                $existing[$answer->field->id] = $answer->field->field_type === 'checkbox'
                    ? [] : $answer->value;
            }
            $request->merge(['answers' => array_replace($existing, $posted)]);
        }

        $emailAnswer = $answers->first(
            fn ($answer) => $answer->field?->field_key === 'email_address'
        );

        if ($emailAnswer) {
            $postedAnswers = $request->input('answers', []);
            $emailFieldId = (string) $emailAnswer->field->id;

            if (is_array($postedAnswers) && array_key_exists($emailFieldId, $postedAnswers)) {
                $postedAnswers[$emailFieldId] = strtolower(trim((string) $postedAnswers[$emailFieldId]));
                $request->merge(['answers' => $postedAnswers]);
            } elseif (is_array($postedAnswers) && array_key_exists((int) $emailFieldId, $postedAnswers)) {
                $postedAnswers[(int) $emailFieldId] = strtolower(trim((string) $postedAnswers[(int) $emailFieldId]));
                $request->merge(['answers' => $postedAnswers]);
            }
        }

        $rules = [
            'job_vacancy_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('job_vacancies', 'id'),
            ],
            'answers' => ['nullable', 'array'],
        ];

        $attributes = [
            'job_vacancy_id' => 'job vacancy',
        ];

        foreach ($answers as $answer) {
            $field = $answer->field;
            $key = 'answers.' . $field->id;
            $attributes[$key] = $field->label;
            $attributes[$key . '.*'] = $field->label;

            $options = collect($field->options ?? [])
                ->map(fn ($option) => trim((string) $option))
                ->filter()
                ->values()
                ->all();

            if ($field->field_type === 'file') {
                $mustUpload = $field->is_required && blank($answer->value);
                $fieldRules = ['bail', $mustUpload ? 'required' : 'nullable'];
                $fieldRules[] = 'file';
                $fieldRules[] = 'mimes:pdf,doc,docx,jpg,jpeg,png';
                $fieldRules[] = 'max:5120';
                $rules[$key] = $fieldRules;
                continue;
            }

            $fieldRules = ['bail', $field->is_required ? 'required' : 'nullable'];

            switch ($field->field_type) {
                case 'email':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'email:rfc';
                    $fieldRules[] = 'max:255';
                    if ($field->field_key === 'email_address') {
                        $fieldRules[] = Rule::unique('applicants', 'email')->ignore($applicant->id);
                    }
                    break;

                case 'number':
                    $fieldRules[] = 'numeric';
                    break;

                case 'date':
                    $fieldRules[] = 'date_format:Y-m-d';
                    if ($field->field_key === 'birthdate'
                        || str_starts_with($field->field_key, 'employment_dates_')
                        || str_starts_with($field->field_key, 'employment_end_')) {
                        $fieldRules[] = 'before_or_equal:today';
                    }
                    break;

                case 'select':
                case 'radio':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    if ($options !== []) {
                        $fieldRules[] = Rule::in($options);
                    }
                    break;

                case 'checkbox':
                    $fieldRules[] = 'array';
                    if ($field->is_required) {
                        $fieldRules[] = 'min:1';
                    }
                    if ($options !== []) {
                        $rules[$key . '.*'] = ['string', Rule::in($options)];
                    }
                    break;

                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:10000';
                    break;

                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;
            }

            $rules[$key] = $fieldRules;
        }

        $validated = $request->validate($rules, [
            'job_vacancy_id.required' => 'Please select a job vacancy.',
            'job_vacancy_id.exists' => 'The selected job vacancy no longer exists.',
            'answers.*.required' => 'This field is required.',
            'answers.*.email' => 'Please enter a valid email address.',
            'answers.*.unique' => 'This email address is already assigned to another applicant record.',
            'answers.*.numeric' => 'This field must contain a valid number.',
            'answers.*.date_format' => 'Please enter a valid date.',
            'answers.*.before_or_equal' => 'The selected date cannot be in the future.',
            'answers.*.file' => 'The uploaded value must be a valid file.',
            'answers.*.mimes' => 'Only PDF, DOC, DOCX, JPG, JPEG, and PNG files are allowed.',
            'answers.*.max' => 'The submitted value exceeds the allowed limit.',
            'answers.*.in' => 'The selected option is invalid.',
            'answers.*.array' => 'Please select one or more valid options.',
            'answers.*.min' => 'Please select at least one option.',
            'answers.*.*.in' => 'One or more selected options are invalid.',
        ], $attributes);

        app(\App\Services\ApplicationEvidenceValidator::class)->validate(
            $request, $answers->pluck('field')->unique('id')->values(), false
        );

        $targetVacancyId = (int) $validated['job_vacancy_id'];

        $duplicateApplication = Application::query()
            ->where('applicant_id', $applicant->id)
            ->where('job_vacancy_id', $targetVacancyId)
            ->whereKeyNot($application->id)
            ->exists();

        if ($duplicateApplication) {
            throw ValidationException::withMessages([
                'job_vacancy_id' => 'This applicant already has another application for the selected job vacancy.',
            ]);
        }

        $oldFilesToDelete = [];
        $newFilesStored = [];

        try {
            DB::transaction(function () use (
                $request,
                $application,
                $applicant,
                $answers,
                $targetVacancyId,
                &$oldFilesToDelete,
                &$newFilesStored
            ) {
                $answerByKey = [];

                foreach ($answers as $answer) {
                    $field = $answer->field;
                    $inputKey = 'answers.' . $field->id;

                    if ($field->field_type === 'file') {
                        $storedValue = $answer->value;

                        if ($request->hasFile($inputKey)) {
                            $storedValue = $request->file($inputKey)
                                ->store('application-documents', 'public');

                            $newFilesStored[] = $storedValue;

                            if (filled($answer->value)) {
                                $oldFilesToDelete[] = $answer->value;
                            }

                            $answer->update(['value' => $storedValue]);
                        }

                        $answerByKey[$field->field_key] = $storedValue;
                        continue;
                    }

                    if ($field->field_type === 'checkbox') {
                        $value = $request->input($inputKey, []);
                        $value = is_array($value) ? array_values($value) : [];
                        $storedValue = json_encode($value, JSON_UNESCAPED_UNICODE);
                    } else {
                        $value = $request->input($inputKey);
                        $storedValue = is_string($value) ? trim($value) : $value;
                        $storedValue = $storedValue === '' ? null : $storedValue;
                    }

                    $answer->update(['value' => $storedValue]);
                    $answerByKey[$field->field_key] = $storedValue;
                }

                $profileData = [];

                if (array_key_exists('first_name', $answerByKey)) {
                    $profileData['first_name'] = $answerByKey['first_name'] ?: $applicant->first_name;
                }
                if (array_key_exists('middle_name', $answerByKey)) {
                    $profileData['middle_name'] = $answerByKey['middle_name'];
                }
                if (array_key_exists('last_name', $answerByKey)) {
                    $profileData['last_name'] = $answerByKey['last_name'] ?: $applicant->last_name;
                }
                if (array_key_exists('email_address', $answerByKey)) {
                    $profileData['email'] = strtolower(trim((string) $answerByKey['email_address']));
                }
                if (array_key_exists('cellphone_number', $answerByKey)) {
                    $profileData['mobile'] = $answerByKey['cellphone_number'] ?: 'N/A';
                }
                if (array_key_exists('present_address', $answerByKey)) {
                    $profileData['address'] = $answerByKey['present_address'] ?: 'N/A';
                }
                if (array_key_exists('birthdate', $answerByKey)) {
                    $profileData['birthdate'] = $answerByKey['birthdate'] ?: null;
                }
                if (array_key_exists('gender', $answerByKey)) {
                    $profileData['gender'] = in_array(
                        $answerByKey['gender'],
                        ['Male', 'Female', 'Prefer not to say'],
                        true
                    ) ? $answerByKey['gender'] : null;
                }
                if (array_key_exists('resume', $answerByKey)) {
                    $profileData['resume_path'] = $answerByKey['resume'];
                }

                if ($profileData !== []) {
                    $applicant->update($profileData);
                }

                if ($application->job_vacancy_id !== $targetVacancyId) {
                    $application->update([
                        'job_vacancy_id' => $targetVacancyId,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            foreach ($newFilesStored as $path) {
                $this->deleteStoredPublicFile($path);
            }

            throw $exception;
        }

        foreach (array_unique($oldFilesToDelete) as $path) {
            $this->deleteStoredPublicFile($path);
        }

        // Any HR edit to role evidence or vacancy assignment immediately refreshes
        // the applicant's assessment. A scoring issue should not roll back the edit.
        $assessmentPending = false;
        try {
            app(AssessmentInsightService::class)->assess($application->fresh());
        } catch (Throwable $exception) {
            $assessmentPending = true;
            // Never leave an old score attached to newly edited evidence.
            $application->assessmentResult()->delete();
            report($exception);
        }

        return response()->json([
            'assessment_pending' => $assessmentPending,
            'message' => $assessmentPending
                ? 'Applicant information saved. Assessment refresh is pending; open Assessment Insights to retry.'
                : 'Applicant information updated successfully.',
        ]);
    }

    public function printForms(Application $application): View
    {
        $application->load([
            'applicant',
            'vacancy.position.department',
            'formSubmissions' => fn ($query) => $query
                ->with([
                    'template',
                    'answers.field',
                ])
                ->orderBy('id'),
        ]);

        $submissions = $application->formSubmissions
            ->sortBy(fn ($submission) => [
                $submission->template?->sort_order ?? PHP_INT_MAX,
                $submission->template?->id ?? PHP_INT_MAX,
            ])
            ->values();

        abort_if($submissions->isEmpty(), 404, 'No submitted forms were found for this applicant.');

        $filename = Str::slug($application->reference_no . '-' . ($application->applicant?->full_name ?? 'applicant'))
            . '-forms.pdf';

        return view('admin.applicants.print', compact('application', 'submissions', 'filename'));
    }

    public function updateStatus(
        Request $request,
        Application $application
    ): JsonResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'max:80',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $application->update([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        $application->statusHistories()->create([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        $application->loadMissing('applicant');
        app(RecruitmentNotificationService::class)->sendToRecruitmentTeam([
            'title' => 'Applicant Stage Updated',
            'message' => ($application->applicant?->full_name ?? $application->reference_no)
                . ' was moved to ' . $validated['status'] . '.',
            'icon' => in_array($validated['status'], ['Hired'], true) ? 'bi-person-check-fill' : 'bi-arrow-repeat',
            'color' => $validated['status'] === 'Hired' ? 'success' : ($validated['status'] === 'Rejected' ? 'danger' : 'warning'),
            'destination' => 'hiring-status.index',
            'parameters' => ['application' => $application->id],
        ], auth()->id());

        return response()->json([
            'message' => 'Application status updated successfully.',
        ]);
    }

    public function destroy(Application $application): JsonResponse
    {
        DB::beginTransaction();

        try {
            $application->load([
                'applicant',
                'interviews',
                'formSubmissions.answers.field',
                'statusHistories',
            ]);

            $applicant = $application->applicant;

            foreach ($application->formSubmissions as $submission) {
                foreach ($submission->answers as $answer) {
                    if ($answer->field?->field_type === 'file' && filled($answer->value)) {
                        $this->deleteStoredPublicFile($answer->value);
                    }
                }

                $submission->answers()->delete();
                $submission->delete();
            }

            $application->interviews()->delete();
            $application->statusHistories()->delete();
            $application->delete();

            if (
                $applicant
                && !Application::query()->where('applicant_id', $applicant->id)->exists()
            ) {
                $applicant->delete();
            }

            DB::commit();

            return response()->json([
                'message' => 'Applicant and all related records were deleted successfully.',
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);

            return response()->json([
                'message' => 'Unable to delete the applicant. Please check the Laravel log.',
            ], 500);
        }
    }

    private function publicFileUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $cleanPath = ltrim($path, '/');

        if (str_starts_with($cleanPath, 'storage/')) {
            return asset($cleanPath);
        }

        return Storage::disk('public')->url($cleanPath);
    }

    private function fileDisplayName(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $pathOnly = parse_url(trim($path), PHP_URL_PATH) ?: trim($path);
        $name = basename($pathOnly);

        return $name !== '' ? urldecode($name) : 'Uploaded file';
    }

    private function deleteStoredPublicFile(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $cleanPath = ltrim($path, '/');

        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, strlen('storage/'));
        }

        Storage::disk('public')->delete($cleanPath);
    }
}
