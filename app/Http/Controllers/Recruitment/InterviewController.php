<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use App\Services\RecruitmentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InterviewController extends Controller
{
    public function index(Request $request): View
    {
        $applications = Application::with(['applicant', 'vacancy'])
            ->whereNotIn('status', ['Hired', 'Rejected', 'Withdrawn'])
            ->latest('applied_at')
            ->get();

        $interviewers = User::whereIn('role', ['admin', 'hr'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $selectedApplicationId = $request->integer('application') ?: null;
        $selectedInterviewId = $request->integer('interview') ?: null;

        return view('recruitment.interviews.index', compact(
            'applications',
            'interviewers',
            'selectedApplicationId',
            'selectedInterviewId'
        ));
    }

    public function data(): JsonResponse
    {
        $data = Interview::with([
                'application.applicant',
                'application.vacancy',
                'interviewer',
            ])
            ->latest('scheduled_at')
            ->get()
            ->map(fn (Interview $interview) => [
                'id' => $interview->id,
                'reference_no' => $interview->application?->reference_no ?? 'N/A',
                'applicant' => $interview->application?->applicant?->full_name ?? 'N/A',
                'position' => $interview->application?->vacancy?->title ?? 'N/A',
                'application_status' => $interview->application?->status ?? 'N/A',
                'type' => $interview->type,
                'scheduled_at' => $interview->scheduled_at?->format('M d, Y g:i A'),
                'interviewer' => $interview->interviewer?->name ?? 'Unassigned',
                'location' => $interview->meeting_link ?: ($interview->location ?: 'TBA'),
                'status' => $interview->status,
            ]);

        return response()->json(['data' => $data]);
    }

    public function show(Interview $interview): JsonResponse
    {
        return response()->json([
            'id' => $interview->id,
            'application_id' => $interview->application_id,
            'interviewer_id' => $interview->interviewer_id,
            'type' => $interview->type,
            'scheduled_at' => $interview->scheduled_at?->format('Y-m-d\TH:i'),
            'location' => $interview->location,
            'meeting_link' => $interview->meeting_link,
            'status' => $interview->status,
            'remarks' => $interview->remarks,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $this->guardAgainstDuplicateActiveInterview($data);

        $interview = Interview::create($data);
        $this->syncApplicationStage($interview);
        $this->notifyInterviewUpdate($interview, 'scheduled');

        return response()->json([
            'message' => 'Interview scheduled successfully. The applicant stage was updated automatically.',
        ]);
    }

    public function update(Request $request, Interview $interview): JsonResponse
    {
        $data = $this->validated($request);
        $this->guardAgainstDuplicateActiveInterview($data, $interview->id);

        $oldApplicationId = $interview->application_id;
        $interview->update($data);
        $interview->refresh();

        if ($oldApplicationId !== $interview->application_id) {
            $oldApplication = Application::find($oldApplicationId);
            if ($oldApplication) {
                $this->recalculateApplicationStage($oldApplication);
            }
        }

        $this->syncApplicationStage($interview);
        $this->notifyInterviewUpdate($interview, 'updated');

        return response()->json([
            'message' => 'Interview updated successfully. The applicant stage was synchronized.',
        ]);
    }

    public function destroy(Interview $interview): JsonResponse
    {
        $application = $interview->application;
        $interview->delete();

        if ($application) {
            $this->recalculateApplicationStage($application);
        }

        return response()->json([
            'message' => 'Interview deleted successfully. The applicant stage was recalculated.',
        ]);
    }

    private function notifyInterviewUpdate(Interview $interview, string $action): void
    {
        $interview->loadMissing(['application.applicant']);
        $application = $interview->application;
        $applicantName = $application?->applicant?->full_name ?? 'Applicant';
        $actionLabel = $action === 'scheduled' ? 'scheduled' : strtolower($interview->status ?: 'updated');

        app(RecruitmentNotificationService::class)->sendToRecruitmentTeam([
            'title' => $action === 'scheduled' ? 'Interview Scheduled' : 'Interview Updated',
            'message' => $interview->type . ' for ' . $applicantName . ' was ' . $actionLabel
                . ' for ' . optional($interview->scheduled_at)->format('M d, Y g:i A') . '.',
            'icon' => $interview->status === 'Completed' ? 'bi-check-circle-fill' : 'bi-calendar-event-fill',
            'color' => $interview->status === 'Completed' ? 'success' : 'primary',
            'destination' => 'interviews.index',
            'parameters' => ['interview' => $interview->id],
        ], auth()->id());
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'application_id' => ['required', 'exists:applications,id'],
            'interviewer_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', 'in:Initial Interview,HR Interview,Technical Interview,Final Interview'],
            'scheduled_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'in:Scheduled,Completed,Cancelled,Rescheduled,No Show'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function guardAgainstDuplicateActiveInterview(array $data, ?int $ignoreId = null): void
    {
        if (!in_array($data['status'], ['Scheduled', 'Rescheduled'], true)) {
            return;
        }

        $duplicate = Interview::query()
            ->where('application_id', $data['application_id'])
            ->where('type', $data['type'])
            ->whereIn('status', ['Scheduled', 'Rescheduled'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'type' => 'This applicant already has an active schedule for the selected interview type. Edit or cancel the existing schedule instead.',
            ]);
        }
    }

    private function syncApplicationStage(Interview $interview): void
    {
        $application = $interview->application;
        if (!$application) {
            return;
        }

        $mapping = $this->stageForInterview($interview);
        if (!$mapping) {
            $this->recalculateApplicationStage($application);
            return;
        }

        [$status, $remarks] = $mapping;
        $this->applyApplicationStage($application, $status, $remarks);
    }

    private function recalculateApplicationStage(Application $application): void
    {
        $latestRelevant = $application->interviews()
            ->whereIn('status', ['Scheduled', 'Rescheduled', 'Completed'])
            ->latest('scheduled_at')
            ->latest('id')
            ->first();

        if (!$latestRelevant) {
            if (!in_array($application->status, ['Hired', 'Rejected', 'Withdrawn'], true)) {
                $this->applyApplicationStage(
                    $application,
                    'For Screening',
                    'No active or completed interview record remains.'
                );
            }
            return;
        }

        $mapping = $this->stageForInterview($latestRelevant);
        if ($mapping) {
            $this->applyApplicationStage($application, $mapping[0], $mapping[1]);
        }
    }

    private function stageForInterview(Interview $interview): ?array
    {
        if (in_array($interview->status, ['Scheduled', 'Rescheduled'], true)) {
            $status = $interview->type === 'Final Interview'
                ? 'For Final Interview'
                : 'For Initial Interview';

            return [
                $status,
                $interview->type . ' scheduled for ' . $interview->scheduled_at->format('M d, Y g:i A') . '.',
            ];
        }

        if ($interview->status === 'Completed') {
            $status = $interview->type === 'Final Interview'
                ? 'For Job Offer'
                : 'For Questionnaire Review';

            return [$status, $interview->type . ' completed.'];
        }

        return null;
    }

    private function applyApplicationStage(Application $application, string $status, string $remarks): void
    {
        if ($application->status === $status) {
            return;
        }

        $application->update(['status' => $status]);
        $application->statusHistories()->create([
            'status' => $status,
            'remarks' => $remarks,
            'updated_by' => auth()->id(),
        ]);
    }
}
