<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use App\Models\Position;
use App\Services\AssessmentInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VacancyController extends Controller
{
    public function index(): View
    {
        JobVacancy::syncExpiredStatuses();

        $positions = Position::query()
            ->with('department')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.vacancies.index', compact('positions'));
    }

    public function data(): JsonResponse
    {
        JobVacancy::syncExpiredStatuses();

        $vacancies = JobVacancy::query()
            ->with('position.department')
            ->withCount('applications')
            ->latest()
            ->get()
            ->map(function (JobVacancy $vacancy) {
                return [
                    'id' => $vacancy->id,
                    'title' => $vacancy->title,
                    'position_name' => $vacancy->position?->name ?? 'N/A',
                    'department_name' => $vacancy->position?->department?->name ?? 'N/A',
                    'employment_type' => $vacancy->employment_type,
                    'slots' => $vacancy->slots,
                    'applications_count' => $vacancy->applications_count,
                    'opening_date' => optional($vacancy->opening_date)->format('M d, Y'),
                    'closing_date' => optional($vacancy->closing_date)->format('M d, Y') ?? 'No deadline',
                    'status' => $vacancy->effective_status,
                ];
            });

        return response()->json(['data' => $vacancies]);
    }

    public function show(JobVacancy $vacancy): JsonResponse
    {
        JobVacancy::syncExpiredStatuses();
        $vacancy->refresh();
        $vacancy->load(['position.department','qualificationsList'])->loadCount('applications');

        return response()->json([
            'id' => $vacancy->id,
            'position_id' => $vacancy->position_id,
            'title' => $vacancy->title,
            'position_name' => $vacancy->position?->name ?? 'N/A',
            'department_name' => $vacancy->position?->department?->name ?? 'N/A',
            'employment_type' => $vacancy->employment_type,
            'slots' => $vacancy->slots,
            'applications_count' => $vacancy->applications_count,
            'qualifications' => $vacancy->qualifications,
            'qualification_items' => $vacancy->qualificationsList->map(fn ($q) => [
                'id' => $q->id,
                'qualification_text' => $q->qualification_text,
                'qualification_type' => $q->qualification_type,
                'requirement_level' => $q->requirement_level,
                'minimum_value' => $q->minimum_value,
                'minimum_unit' => $q->minimum_unit,
                'evidence_source' => $q->evidence_source,
                'importance' => $q->importance,
            ])->values(),
            'salary_min' => $vacancy->salary_min,
            'salary_max' => $vacancy->salary_max,
            'opening_date' => optional($vacancy->opening_date)->format('M d, Y'),
            'closing_date' => optional($vacancy->closing_date)->format('M d, Y') ?? 'No deadline',
            'opening_date_raw' => optional($vacancy->opening_date)->format('Y-m-d'),
            'closing_date_raw' => optional($vacancy->closing_date)->format('Y-m-d'),
            'status' => $vacancy->effective_status,
            'poster_path' => $vacancy->poster_path,
            'poster_url' => $vacancy->poster_path ? Storage::disk('public')->url($vacancy->poster_path) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $position = Position::query()->findOrFail($validated['position_id']);

        $payload = collect($validated)->except('poster')->all();
        $payload['title'] = $position->name;
        $payload['description'] = null;
        $payload = $this->normalizeStatusForDates($payload);

        if ($request->hasFile('poster')) {
            $payload['poster_path'] = $request->file('poster')->store('job-posters', 'public');
        }

        $vacancy = DB::transaction(function () use ($payload, $validated) {
            $vacancy = JobVacancy::create($payload);
            $this->syncQualifications($vacancy, $validated['qualification_items'] ?? [], $validated['qualifications'] ?? '');
            return $vacancy;
        });

        return response()->json([
            'message' => 'Job vacancy added successfully.',
            'data' => $vacancy,
        ]);
    }

    public function update(Request $request, JobVacancy $vacancy): JsonResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $position = Position::query()->findOrFail($validated['position_id']);

        $payload = collect($validated)->except('poster')->all();
        $payload['title'] = $position->name;
        $payload['description'] = null;
        $payload = $this->normalizeStatusForDates($payload, $vacancy);

        $oldPoster = $vacancy->poster_path;
        $wasClosed = $vacancy->status === 'Closed';
        $willReopen = $wasClosed && ($payload['status'] ?? null) === 'Open';
        $hasNewPoster = $request->hasFile('poster');

        if ($hasNewPoster) {
            $payload['poster_path'] = $request->file('poster')->store('job-posters', 'public');
        }

        DB::transaction(function () use ($vacancy, $payload, $validated) {
            $vacancy->update($payload);
            $this->syncQualifications($vacancy, $validated['qualification_items'] ?? [], $validated['qualifications'] ?? '');
        });

        if ($hasNewPoster && $oldPoster) {
            Storage::disk('public')->delete($oldPoster);
        }

        // Vacancy qualifications are the assessment baseline. Re-score existing
        // applicants immediately whenever HR changes that baseline.
        if ($vacancy->applications()->exists()) {
            try {
                app(AssessmentInsightService::class)->assessVacancy($vacancy->fresh());
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'message' => $willReopen
                ? 'Job vacancy updated successfully and reopened because the expired closing date was extended.'
                : 'Job vacancy updated successfully.',
            'status' => $payload['status'] ?? $vacancy->fresh()->effective_status,
        ]);
    }

    public function destroy(JobVacancy $vacancy): JsonResponse
    {
        if ($vacancy->applications()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a vacancy with existing applications. You may close or cancel it instead.',
            ], 422);
        }

        if ($vacancy->poster_path) {
            Storage::disk('public')->delete($vacancy->poster_path);
        }

        $vacancy->delete();

        return response()->json([
            'message' => 'Job vacancy deleted successfully.',
        ]);
    }

    private function normalizeStatusForDates(array $payload, ?JobVacancy $existing = null): array
    {
        $status = (string) ($payload['status'] ?? 'Draft');
        $opening = !empty($payload['opening_date'])
            ? \Illuminate\Support\Carbon::parse($payload['opening_date'])->startOfDay()
            : null;
        $closing = !empty($payload['closing_date'])
            ? \Illuminate\Support\Carbon::parse($payload['closing_date'])->startOfDay()
            : null;
        $today = today()->startOfDay();

        // Date-driven closure is always enforced for a vacancy that HR marked Open.
        if ($status === 'Open' && $closing && $closing->lt($today)) {
            $payload['status'] = 'Closed';
            return $payload;
        }

        // If a vacancy was previously auto-closed because its old deadline passed,
        // extending/removing that expired deadline should reopen it automatically.
        // A vacancy that was manually Closed while its old deadline was still valid
        // stays Closed, so HR retains an intentional early-close control.
        if ($existing && $status === 'Closed' && $existing->status === 'Closed') {
            $oldClosing = $existing->closing_date?->copy()->startOfDay();
            $oldExpired = $oldClosing && $oldClosing->lt($today);
            $oldDate = $oldClosing?->toDateString();
            $newDate = $closing?->toDateString();
            $deadlineChanged = $oldDate !== $newDate;
            $insideApplicationWindow = (!$opening || $opening->lte($today))
                && (!$closing || $closing->gte($today));

            if ($oldExpired && $deadlineChanged && $insideApplicationWindow) {
                $payload['status'] = 'Open';
            }
        }

        return $payload;
    }

    private function messages(): array
    {
        return [
            'qualifications.required' => 'Qualifications are required.',
            'poster.dimensions' => 'The job poster must use a 3:4 portrait aspect ratio, for example 900 x 1200 pixels.',
            'poster.image' => 'The job poster must be a valid image file.',
            'poster.mimes' => 'The job poster must be a JPG, JPEG, PNG, or WEBP image.',
            'poster.max' => 'The job poster must not be larger than 5 MB.',
        ];
    }

    private function syncQualifications(JobVacancy $vacancy, array $items, string $fallbackText): void
    {
        $clean = collect($items)
            ->flatMap(function ($row) {
                $text = trim((string) ($row['qualification_text'] ?? ''));
                if ($text === '') {
                    return [];
                }
                $parts = preg_split('/\r\n|\r|\n|;/', $text) ?: [$text];
                return collect($parts)
                    ->map(fn ($part) => ['qualification_text' => trim((string) preg_replace('/^[\-•*\s]+/u', '', (string) $part))])
                    ->filter(fn ($part) => $part['qualification_text'] !== '')
                    ->values()
                    ->all();
            })
            ->values();

        if ($clean->isEmpty()) {
            $lines = preg_split('/\r\n|\r|\n|;/', $fallbackText) ?: [];
            $clean = collect($lines)->map(fn ($line) => [
                'qualification_text' => trim((string) preg_replace('/^[\-•*\s]+/u', '', (string) $line)),
            ])->filter(fn ($row) => $row['qualification_text'] !== '')->values();
        }

        $vacancy->qualificationsList()->delete();
        foreach ($clean as $index => $row) {
            $text = trim((string) $row['qualification_text']);
            $auto = $this->inferQualificationMetadata($text);

            $vacancy->qualificationsList()->create([
                'qualification_text' => $text,
                'qualification_type' => $auto['qualification_type'],
                'requirement_level' => $auto['requirement_level'],
                'minimum_value' => $auto['minimum_value'],
                'minimum_unit' => $auto['minimum_unit'],
                'evidence_source' => $auto['evidence_source'],
                'importance' => $auto['importance'],
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }

        if ($clean->isNotEmpty()) {
            $vacancy->updateQuietly([
                'qualifications' => $clean->pluck('qualification_text')->implode("\n"),
            ]);
        }
    }

    /**
     * Convert a plain-language vacancy qualification into structured assessment metadata.
     * HR only needs to type the requirement; the system decides how Assessment Insights
     * should verify it. This intentionally remains conservative: uncertain details are
     * left without a minimum rather than inventing one.
     */
    private function inferQualificationMetadata(string $text): array
    {
        if (preg_match('/\b(degree|graduate|graduated|bachelor|college|course|diploma|bs\s*accountancy|bsba|bsit|bscs)\b/i', $text)) {
            $type = 'education';
        } elseif (preg_match('/\b(certif(?:ied|ication)?|license(?:d)?|licensure|training|nc\s?ii|nc2)\b/i', $text)) {
            $type = 'certification';
        } elseif (preg_match('/\b(available|availability|immediate(?:ly)?|onsite|on-site|shift|schedule|willing to work|start date)\b/i', $text)) {
            $type = 'availability';
        } elseif (
            preg_match('/\b(experience\s+using|proficien|knowledge\s+of|familiar\s+with|software|system|tool|netsuite|quickbooks|excel)\b/i', $text)
            && !preg_match('/\b\d+(?:\.\d+)?\s*(years?|yrs?|months?|mos?)\b/i', $text)
        ) {
            // Tool/software requirements often contain the word "experience" but
            // should be verified from skill evidence, not employment duration.
            $type = 'skill';
        } elseif (preg_match('/\b(year|years|yr|yrs|month|months|mo|mos|experience as|experience in|worked as|work experience)\b/i', $text)) {
            $type = 'experience';
        } else {
            $type = 'skill';
        }

        $minimumValue = null;
        $minimumUnit = null;
        if (preg_match('/\b(?:at\s+least\s+|minimum(?:\s+of)?\s+)?(\d+(?:\.\d+)?)\s*(years?|yrs?|months?|mos?)\b/i', $text, $match)) {
            $minimumValue = (float) $match[1];
            $minimumUnit = str_starts_with(strtolower($match[2]), 'mo') ? 'months' : 'years';
        }

        // Required language wins when a sentence also contains a preferred
        // sub-clause (e.g. "Must know an accounting system; NetSuite is an advantage").
        if (preg_match('/\b(must|required|mandatory|at\s+least|minimum)\b/i', $text)) {
            $level = 'required';
        } elseif (preg_match('/\b(nice\s+to\s+have|bonus|optional)\b/i', $text)) {
            $level = 'nice_to_have';
        } elseif (preg_match('/\b(preferred|preferably|advantage|an advantage|plus|desirable)\b/i', $text)) {
            $level = 'preferred';
        } else {
            $level = 'required';
        }

        if (preg_match('/\b(mandatory|must|required|required qualification|license required|licensed)\b/i', $text)) {
            $importance = 'critical';
        } elseif ($level === 'required') {
            $importance = 'high';
        } elseif ($level === 'preferred') {
            $importance = 'medium';
        } else {
            $importance = 'low';
        }

        $evidenceSource = match ($type) {
            'experience' => 'employment_history',
            'education', 'certification' => 'education',
            'availability' => 'availability',
            'skill' => 'skills',
            default => 'auto',
        };

        return [
            'qualification_type' => $type,
            'requirement_level' => $level,
            'minimum_value' => $minimumValue,
            'minimum_unit' => $minimumUnit,
            'evidence_source' => $evidenceSource,
            'importance' => $importance,
        ];
    }

    private function rules(): array
    {
        return [
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'slots' => ['required', 'integer', 'min:1'],
            'employment_type' => ['required', 'in:Full-time,Part-time,Contract,Internship'],
            'qualifications' => ['required', 'string', 'max:10000'],
            'qualification_items' => ['nullable','array','max:30'],
            'qualification_items.*.qualification_text' => ['required','string','max:1000'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:ratio=3/4'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'opening_date' => ['required', 'date'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:opening_date'],
            'status' => ['required', 'in:Draft,Open,Closed,Cancelled'],
        ];
    }
}
