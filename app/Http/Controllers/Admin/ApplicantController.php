<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
                    'applied_at' => optional($application->applied_at)
                    ->format('M d, Y'),
                    'status' => $application->status,
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
                'examResults',
                'statusHistories',
            ])
        );
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

        return response()->json([
            'message' => 'Application status updated successfully.',
        ]);
    }
}