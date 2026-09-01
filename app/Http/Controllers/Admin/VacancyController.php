<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $vacancy->load('position.department')->loadCount('applications');

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

        $vacancy = JobVacancy::create($payload);

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
        $payload = $this->normalizeStatusForDates($payload);

        $oldPoster = $vacancy->poster_path;
        $hasNewPoster = $request->hasFile('poster');

        if ($hasNewPoster) {
            $payload['poster_path'] = $request->file('poster')->store('job-posters', 'public');
        }

        $vacancy->update($payload);

        if ($hasNewPoster && $oldPoster) {
            Storage::disk('public')->delete($oldPoster);
        }

        return response()->json([
            'message' => 'Job vacancy updated successfully.',
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

    private function normalizeStatusForDates(array $payload): array
    {
        if (
            ($payload['status'] ?? null) === 'Open'
            && !empty($payload['closing_date'])
            && \Illuminate\Support\Carbon::parse($payload['closing_date'])->lt(today())
        ) {
            $payload['status'] = 'Closed';
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

    private function rules(): array
    {
        return [
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'slots' => ['required', 'integer', 'min:1'],
            'employment_type' => ['required', 'in:Full-time,Part-time,Contract,Internship'],
            'qualifications' => ['required', 'string', 'max:10000'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:ratio=3/4'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'opening_date' => ['required', 'date'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:opening_date'],
            'status' => ['required', 'in:Draft,Open,Closed,Cancelled'],
        ];
    }
}
