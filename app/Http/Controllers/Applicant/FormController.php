<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\FormAnswer;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\JobVacancy;
use App\Services\RecruitmentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FormController extends Controller
{
    private const DUPLICATE_APPLICATION_MESSAGE = 'An application using this email address has already been submitted for this vacancy. Please use a different email address or return to the job openings to choose another position.';

    public function careers(): View
    {
        JobVacancy::syncExpiredStatuses();

        $vacancies = $this->activeVacancyQuery()
            ->with('position.department')
            ->latest('created_at')
            ->latest('id')
            ->get();

        return view('applicant.careers.index', compact('vacancies'));
    }

    public function selectVacancy(Request $request, JobVacancy $vacancy): RedirectResponse
    {
        $vacancy = $this->activeVacancyQuery()
            ->whereKey($vacancy->id)
            ->first();

        if (!$vacancy) {
            return redirect()->route('careers.index')
                ->with('career_error', 'That job vacancy is no longer accepting applications.');
        }

        // The selected vacancy is stored server-side. The application page does not
        // accept a vacancy id from the URL or from a hidden form field.
        $request->session()->put('selected_job_vacancy_id', $vacancy->id);
        $request->session()->put('selected_job_vacancy_at', now()->timestamp);

        return redirect()->route('careers.apply');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $vacancy = $this->selectedVacancyFromSession($request);

        if (!$vacancy) {
            return redirect()->to(route('careers.index') . '#open-positions')
                ->with('career_error', 'Please select an open position before starting your application.');
        }

        $templates = FormTemplate::with([
            'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $existingAnswers = collect();

        return view('applicant.form.index', compact(
            'templates',
            'vacancy',
            'existingAnswers'
        ));
    }

    public function validateStep(Request $request): JsonResponse
    {
        if (!$this->selectedVacancyFromSession($request)) {
            throw ValidationException::withMessages([
                'form' => 'Your selected job vacancy is missing or no longer available. Please return to the careers page and select an open position.',
            ]);
        }

        $request->validate([
            'step_type' => ['required', Rule::in(['form_section'])],
            'template_id' => ['nullable', 'integer'],
            'section' => ['nullable', 'string', 'max:150'],
        ]);

        $request->validate([
            'template_id' => [
                'required',
                'integer',
                Rule::exists('form_templates', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'section' => ['required', 'string', 'max:150'],
        ]);

        $template = FormTemplate::query()
            ->whereKey($request->integer('template_id'))
            ->where('is_active', true)
            ->first();

        if (!$template) {
            throw ValidationException::withMessages([
                'template_id' => 'The selected employment form is unavailable.',
            ]);
        }

        $fields = $template->fields()
            ->where('section', $request->input('section'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($fields->isEmpty()) {
            throw ValidationException::withMessages([
                'section' => 'The selected employment form section is unavailable.',
            ]);
        }

        $request->validate(
            $this->buildFieldRules($fields),
            $this->validationMessages(),
            $this->validationAttributes($fields)
        );

        return response()->json(['valid' => true]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $vacancy = $this->selectedVacancyFromSession($request);

        if (!$vacancy) {
            return redirect()->to(route('careers.index') . '#open-positions')
                ->with('career_error', 'Please select an open position before submitting an application.');
        }

        $templates = FormTemplate::with([
            'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ])
            ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();

        if ($templates->isEmpty()) {
            throw ValidationException::withMessages([
                'form' => 'No active employment form is currently available.',
            ]);
        }

        $allFields = $templates->flatMap(fn ($template) => $template->fields)->values();
        $emailField = $allFields->firstWhere('field_key', 'email_address');

        if (!$emailField) {
            throw ValidationException::withMessages([
                'form' => 'The active Application for Employment template must contain an email_address field.',
            ]);
        }

        $rules = [
            'answers' => ['required', 'array'],
        ];

        $rules = array_merge($rules, $this->buildFieldRules($allFields));

        $request->validate(
            $rules,
            $this->validationMessages(),
            $this->validationAttributes($allFields)
        );

        $answerByKey = [];
        foreach ($allFields as $field) {
            $answerByKey[$field->field_key] = $request->input('answers.' . $field->id);
        }

        $email = strtolower(trim((string) ($answerByKey['email_address'] ?? '')));
        $firstName = trim((string) ($answerByKey['first_name'] ?? 'Applicant'));
        $lastName = trim((string) ($answerByKey['last_name'] ?? ''));

        if ($email === '') {
            throw ValidationException::withMessages([
                'answers.' . $emailField->id => 'Email address is required.',
            ]);
        }

        $existingApplicant = Applicant::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existingApplicant && $existingApplicant->applications()
                ->where('job_vacancy_id', $vacancy->id)->exists()) {
            throw ValidationException::withMessages([
                'form' => self::DUPLICATE_APPLICATION_MESSAGE,
                'answers.' . $emailField->id => self::DUPLICATE_APPLICATION_MESSAGE,
            ]);
        }

        $application = DB::transaction(function () use (
            $request,
            $templates,
            $vacancy,
            $answerByKey,
            $email,
            $firstName,
            $lastName,
            $existingApplicant
        ) {
            $profile = $existingApplicant ?: new Applicant();
            $profile->fill([
                'user_id' => null,
                'first_name' => $firstName,
                'middle_name' => $answerByKey['middle_name'] ?? null,
                'last_name' => $lastName ?: 'Applicant',
                'email' => $email,
                'mobile' => $answerByKey['cellphone_number'] ?? 'N/A',
                'address' => $answerByKey['present_address'] ?? 'N/A',
                'birthdate' => $answerByKey['birthdate'] ?? null,
                'gender' => in_array(
                    $answerByKey['gender'] ?? null,
                    ['Male', 'Female', 'Prefer not to say'],
                    true
                ) ? $answerByKey['gender'] : null,
            ]);
            $profile->save();

            $application = Application::create([
                'applicant_id' => $profile->id,
                'job_vacancy_id' => $vacancy->id,
                'reference_no' => $this->nextReferenceNumber(),
                'status' => 'New Applicant',
                'applied_at' => now(),
            ]);

            $application->statusHistories()->create([
                'status' => 'New Applicant',
                'remarks' => 'Public employment application and questionnaire submitted.',
                'updated_by' => null,
            ]);

            foreach ($templates as $template) {
                $submission = FormSubmission::create([
                    'application_id' => $application->id,
                    'form_template_id' => $template->id,
                    'submitted_by' => null,
                    'submitted_at' => now(),
                ]);

                foreach ($template->fields as $field) {
                    $inputKey = 'answers.' . $field->id;
                    $value = $request->input($inputKey);

                    if ($field->field_type === 'file' && $request->hasFile($inputKey)) {
                        $value = $request->file($inputKey)
                            ->store('application-documents', 'public');

                        if ($field->field_key === 'resume') {
                            $profile->update(['resume_path' => $value]);
                        }
                    }

                    if (is_array($value)) {
                        $value = json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
                    }

                    FormAnswer::create([
                        'form_submission_id' => $submission->id,
                        'form_field_id' => $field->id,
                        'value' => $value,
                    ]);
                }
            }

            return $application;
        });

        $application->loadMissing(['applicant', 'vacancy']);

        app(RecruitmentNotificationService::class)->sendToRecruitmentTeam([
            'title' => 'New Applicant',
            'message' => ($application->applicant?->full_name ?? 'An applicant')
                . ' applied for ' . ($application->vacancy?->title ?? 'an open position')
                . ' (' . $application->reference_no . ').',
            'icon' => 'bi-person-plus-fill',
            'color' => 'danger',
            'destination' => 'applicants.index',
            'parameters' => ['application' => $application->id],
        ]);

        $request->session()->forget(['selected_job_vacancy_id', 'selected_job_vacancy_at']);

        return redirect()->route('careers.success')
            ->with('application_reference', $application->reference_no)
            ->with('applicant_name', trim($firstName . ' ' . $lastName));
    }

    public function success(): View|RedirectResponse
    {
        if (!session()->has('application_reference')) {
            return redirect()->route('careers.index');
        }

return view('applicant.form.success');
}

private function activeVacancyQuery()
{
    return JobVacancy::query()->openForApplications();
}

private function selectedVacancyFromSession(Request $request): ?JobVacancy
{
    $selectedId = $request->session()->get('selected_job_vacancy_id');
    $selectedAt = (int) $request->session()->get('selected_job_vacancy_at', 0);

    // Do not keep a stale selection around indefinitely. Laravel sessions normally
    // expire too, but this keeps the application entry gate explicit.
    if (!$selectedId || !$selectedAt || now()->timestamp - $selectedAt > 7200) {
        $request->session()->forget(['selected_job_vacancy_id', 'selected_job_vacancy_at']);
        return null;
    }

    $vacancy = $this->activeVacancyQuery()
        ->with('position.department')
        ->whereKey($selectedId)
        ->first();

    if (!$vacancy) {
        $request->session()->forget(['selected_job_vacancy_id', 'selected_job_vacancy_at']);
    }

    return $vacancy;
}

private function buildFieldRules(Collection $fields): array
{
    $rules = [];

    foreach ($fields as $field) {
        $key = 'answers.' . $field->id;
        $fieldRules = ['bail', $field->is_required ? 'required' : 'nullable'];
        $options = collect($field->options ?? [])
            ->map(fn ($option) => trim((string) $option))
                ->filter()
            ->values()
            ->all();

            switch ($field->field_type) {
                case 'email':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'email:rfc';
                    $fieldRules[] = 'max:255';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    $fieldRules[] = 'before_or_equal:today';
                    break;
                case 'file':
                    $fieldRules[] = 'file';
                    $fieldRules[] = 'mimes:pdf,doc,docx,jpg,jpeg,png';
                    $fieldRules[] = 'max:5120';
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

    return $rules;
}

private function validationMessages(): array
{
    return [
        'answers.required' => 'The employment application form is required.',
        'answers.array' => 'The submitted employment form data is invalid.',
        'answers.*.required' => 'This field is required.',
        'answers.*.email' => 'Please enter a valid email address.',
        'answers.*.numeric' => 'This field must contain a valid number.',
        'answers.*.date' => 'Please enter a valid date.',
        'answers.*.before_or_equal' => 'The selected date cannot be in the future.',
        'answers.*.file' => 'The uploaded value must be a valid file.',
        'answers.*.mimes' => 'Only PDF, DOC, DOCX, JPG, JPEG, and PNG files are allowed.',
        'answers.*.max' => 'The submitted value exceeds the allowed limit.',
        'answers.*.in' => 'The selected option is invalid.',
        'answers.*.array' => 'Please select one or more valid options.',
        'answers.*.min' => 'Please select at least one option.',
        'answers.*.*.in' => 'One or more selected options are invalid.',
    ];
}

private function validationAttributes(Collection $fields): array
{
    $attributes = [
        'answers' => 'employment application',
    ];

    foreach ($fields as $field) {
        $attributes['answers.' . $field->id] = $field->label;
        $attributes['answers.' . $field->id . '.*'] = $field->label;
    }

    return $attributes;
}

private function nextReferenceNumber(): string
{
    do {
        $reference = 'RS8-' . now()->format('Y') . '-' . strtoupper(Str::random(6));
    } while (Application::where('reference_no', $reference)->exists());

    return $reference;
}
}
