<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use App\Models\JobVacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $summary = [
            'applications' => Application::count(),
            'hired' => Application::where('status', 'Hired')->count(),
            'rejected' => Application::where('status', 'Rejected')->count(),
            'open_vacancies' => JobVacancy::openForApplications()->count(),
            'scheduled_interviews' => Interview::whereIn('status', ['Scheduled', 'Rescheduled'])->count(),
        ];
        $byStatus = Application::selectRaw('status, COUNT(*) total')->groupBy('status')->orderByDesc('total')->get();
        $byPosition = Application::join('job_vacancies', 'applications.job_vacancy_id', '=', 'job_vacancies.id')
            ->selectRaw('job_vacancies.title, COUNT(*) total')->groupBy('job_vacancies.title')->orderByDesc('total')->get();
        return view('recruitment.reports.index', compact('summary', 'byStatus', 'byPosition'));
    }

    public function export(Request $request)
    {
        $rows = Application::with(['applicant', 'vacancy.position.department'])->latest('applied_at')->get();
        return Response::streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Reference','Applicant','Email','Position','Department','Status','Applied']);
            foreach ($rows as $a) {
                fputcsv($out, array_map([self::class, 'csvCell'], [
                    $a->reference_no,
                    $a->applicant?->full_name,
                    $a->applicant?->email,
                    $a->vacancy?->title,
                    $a->vacancy?->position?->department?->name,
                    $a->status,
                    $a->applied_at?->format('Y-m-d H:i:s'),
                ]));
            }
            fclose($out);
        }, 'recruitment-report-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }
    public static function csvCell($value): string
    {
        $value = (string) ($value ?? '');
        return preg_match('/^[\s\x00-\x1F]*[=+@-]/u', $value) ? "'" . $value : $value;
    }
}
