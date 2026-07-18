<?php
namespace App\Http\Controllers\HR;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ExamResult;
use App\Models\Interview;
use App\Models\JobVacancy;
use Illuminate\View\View;
class DashboardController extends Controller {
 public function index(): View {
  $statistics=['new_applicants'=>Application::where('status','New Applicant')->count(),'for_screening'=>Application::where('status','For Screening')->count(),'today_interviews'=>Interview::whereDate('scheduled_at',today())->where('status','Scheduled')->count(),'pending_results'=>Application::where('status','For Examination')->whereDoesntHave('examResults')->count(),'for_job_offer'=>Application::where('status','For Job Offer')->count(),'active_vacancies'=>JobVacancy::where('status','Open')->count()];
  $priorityApplicants=Application::with(['applicant','vacancy'])->whereIn('status',['New Applicant','For Screening','For Initial Interview','For Examination','For Final Interview','For Job Offer'])->latest('updated_at')->take(8)->get()->map(fn($a)=>['name'=>$a->applicant->full_name,'position'=>$a->vacancy->title,'stage'=>$a->status,'updated'=>$a->updated_at->diffForHumans()]);
  $todaySchedule=Interview::with('application.applicant')->whereDate('scheduled_at',today())->where('status','Scheduled')->orderBy('scheduled_at')->get()->map(fn($i)=>['time'=>$i->scheduled_at->format('g:i A'),'name'=>$i->application->applicant->full_name,'type'=>$i->type]);
  return view('hr.dashboard.index',compact('statistics','priorityApplicants','todaySchedule'));
 }
}
