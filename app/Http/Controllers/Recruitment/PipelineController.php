<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\RecruitmentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PipelineController extends Controller
{
    public function index(): View
    {
        $stages = ['New Applicant','For Screening','For Initial Interview','For Questionnaire Review','For Final Interview','For Job Offer','Hired','Rejected','Withdrawn'];
        $counts = Application::selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');
        return view('recruitment.pipeline.index', compact('stages', 'counts'));
    }

    public function data(): JsonResponse
    {
        $data = Application::with(['applicant', 'vacancy.position.department'])->latest('updated_at')->get()->map(fn ($application) => [
            'id' => $application->id,
            'reference_no' => $application->reference_no,
            'applicant' => $application->applicant?->full_name ?? 'N/A',
            'position' => $application->vacancy?->title ?? 'N/A',
            'department' => $application->vacancy?->position?->department?->name ?? 'N/A',
            'status' => $application->status,
            'updated_at' => $application->updated_at?->format('M d, Y g:i A'),
        ]);
        return response()->json(['data' => $data]);
    }

    public function update(Request $request, Application $application): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:New Applicant,For Screening,For Initial Interview,For Questionnaire Review,For Final Interview,For Job Offer,Hired,Rejected,Withdrawn'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $application->update($data);
        $application->statusHistories()->create([
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        $application->loadMissing('applicant');
        app(RecruitmentNotificationService::class)->sendToRecruitmentTeam([
            'title' => 'Hiring Status Updated',
            'message' => ($application->applicant?->full_name ?? $application->reference_no)
                . ' was moved to ' . $data['status'] . '.',
            'icon' => $data['status'] === 'Hired' ? 'bi-person-check-fill' : 'bi-arrow-repeat',
            'color' => $data['status'] === 'Hired' ? 'success' : ($data['status'] === 'Rejected' ? 'danger' : 'warning'),
            'destination' => 'hiring-status.index',
            'parameters' => ['application' => $application->id],
        ], auth()->id());

        return response()->json(['message' => 'Hiring status updated successfully.']);
    }
}
