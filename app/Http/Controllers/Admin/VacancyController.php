<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VacancyController extends Controller
{
    public function index(): View
    {
        $positions = Position::query()
            ->with('department')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.vacancies.index', compact('positions'));
    }

    public function data(): JsonResponse
    {
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
                    'department_name' => $vacancy
                        ->position?->department?->name ?? 'N/A',
                    'employment_type' => $vacancy->employment_type,
                    'slots' => $vacancy->slots,
                    'applications_count' => $vacancy->applications_count,
                    'opening_date' => optional($vacancy->opening_date)
                        ->format('M d, Y'),
                    'closing_date' => optional($vacancy->closing_date)
                        ->format('M d, Y') ?? 'No deadline',
                    'status' => $vacancy->status,
                ];
            });

        return response()->json([
            'data' => $vacancies,
        ]);
    }

    public function show(JobVacancy $vacancy): JsonResponse
    {
        $vacancy->load('position.department')
            ->loadCount('applications');

        return response()->json([
            'id' => $vacancy->id,
            'position_id' => $vacancy->position_id,
            'title' => $vacancy->title,
            'position_name' => $vacancy->position?->name ?? 'N/A',
            'department_name' => $vacancy
                ->position?->department?->name ?? 'N/A',
            'employment_type' => $vacancy->employment_type,
            'slots' => $vacancy->slots,
            'applications_count' => $vacancy->applications_count,
            'description' => $vacancy->description,
            'qualifications' => $vacancy->qualifications,
            'salary_min' => $vacancy->salary_min,
            'salary_max' => $vacancy->salary_max,
            'opening_date' => optional($vacancy->opening_date)
                ->format('M d, Y'),
            'closing_date' => optional($vacancy->closing_date)
                ->format('M d, Y') ?? 'No deadline',
            'opening_date_raw' => optional($vacancy->opening_date)
                ->format('Y-m-d'),
            'closing_date_raw' => optional($vacancy->closing_date)
                ->format('Y-m-d'),
            'status' => $vacancy->status,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $vacancy = JobVacancy::create($validated);

        return response()->json([
            'message' => 'Job vacancy added successfully.',
            'data' => $vacancy,
        ]);
    }

    public function update(
        Request $request,
        JobVacancy $vacancy
    ): JsonResponse {
        $validated = $request->validate($this->rules());

        $vacancy->update($validated);

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

        $vacancy->delete();

        return response()->json([
            'message' => 'Job vacancy deleted successfully.',
        ]);
    }

    private function rules(): array
    {
        return [
            'position_id' => [
                'required',
                'integer',
                'exists:positions,id',
            ],
            'title' => [
                'required',
                'string',
                'max:150',
            ],
            'slots' => [
                'required',
                'integer',
                'min:1',
            ],
            'employment_type' => [
                'required',
                'in:Full-time,Part-time,Contract,Internship',
            ],
            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'qualifications' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'salary_min' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'salary_max' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:salary_min',
            ],
            'opening_date' => [
                'required',
                'date',
            ],
            'closing_date' => [
                'nullable',
                'date',
                'after_or_equal:opening_date',
            ],
            'status' => [
                'required',
                'in:Draft,Open,Closed,Cancelled',
            ],
        ];
    }
}
