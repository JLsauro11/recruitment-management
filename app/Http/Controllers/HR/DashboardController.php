<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FormSubmission;
use App\Models\Interview;
use App\Models\JobVacancy;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statistics = [
            'new_applicants' => Application::where('status', 'New Applicant')->count(),
            'for_screening' => Application::where('status', 'For Screening')->count(),
            'today_interviews' => Interview::whereDate('scheduled_at', today())->where('status', 'Scheduled')->count(),
            'pending_results' => Application::where('status', 'For Questionnaire Review')->count(),
            'for_job_offer' => Application::where('status', 'For Job Offer')->count(),
            'active_vacancies' => JobVacancy::openForApplications()->count(),
        ];

        $priorityApplicants = Application::with(['applicant','vacancy'])
            ->whereIn('status', ['New Applicant','For Screening','For Initial Interview','For Questionnaire Review','For Final Interview','For Job Offer'])
            ->latest('updated_at')->take(8)->get()->map(fn ($application) => [
                'name' => $application->applicant->full_name,
                'position' => $application->vacancy->title,
                'stage' => $application->status,
                'updated' => $application->updated_at->diffForHumans(),
            ]);

        $todaySchedule = Interview::with('application.applicant')
            ->whereDate('scheduled_at', today())->where('status', 'Scheduled')
            ->orderBy('scheduled_at')->get()->map(fn ($interview) => [
                'time' => $interview->scheduled_at->format('g:i A'),
                'name' => $interview->application->applicant->full_name,
                'type' => $interview->type,
            ]);

        return view('hr.dashboard.index', compact('statistics','priorityApplicants','todaySchedule'));
    }
}
