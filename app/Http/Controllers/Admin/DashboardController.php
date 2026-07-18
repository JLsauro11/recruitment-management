<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Interview;
use App\Models\JobVacancy;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statistics = [
            'total_applicants' => Applicant::count(),
            'new_applicants' => Application::where('status', 'New Applicant')->count(),
            'for_screening' => Application::where('status', 'For Screening')->count(),
            'scheduled_interviews' => Interview::where('status', 'Scheduled')->count(),
            'for_examination' => Application::where('status', 'For Examination')->count(),
            'hired_applicants' => Application::where('status', 'Hired')->count(),
            'rejected_applicants' => Application::where('status', 'Rejected')->count(),
            'active_vacancies' => JobVacancy::where('status', 'Open')->count(),
        ];

        $recentApplicants = Application::with(['applicant', 'vacancy'])
            ->latest('applied_at')->take(5)->get()->map(fn ($application) => [
                'name' => $application->applicant->full_name,
                'position' => $application->vacancy->title,
                'date' => $application->applied_at->format('M d, Y'),
                'status' => $application->status,
            ]);

        $upcomingInterviews = Interview::with('application.applicant', 'application.vacancy')
            ->where('status', 'Scheduled')->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')->take(5)->get()->map(fn ($interview) => [
                'name' => $interview->application->applicant->full_name,
                'position' => $interview->application->vacancy->title,
                'date' => $interview->scheduled_at->format('M d'),
                'time' => $interview->scheduled_at->format('g:i A'),
            ]);

        $trendLabels = collect(range(5, 0))->map(fn ($monthsAgo) => now()->subMonths($monthsAgo)->format('M'));
        $trendValues = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            return Application::whereYear('applied_at', $date->year)->whereMonth('applied_at', $date->month)->count();
        });

        $statusLabels = ['For Screening', 'For Initial Interview', 'For Examination', 'For Job Offer', 'Hired', 'Rejected'];
        $statusValues = collect($statusLabels)->map(fn ($status) => Application::where('status', $status)->count());

        return view('admin.dashboard.index', compact(
            'statistics', 'recentApplicants', 'upcomingInterviews',
            'trendLabels', 'trendValues', 'statusLabels', 'statusValues'
        ));
    }
}
