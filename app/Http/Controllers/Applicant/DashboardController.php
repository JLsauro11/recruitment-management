<?php
namespace App\Http\Controllers\Applicant;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\JobVacancy;
use Illuminate\View\View;
class DashboardController extends Controller {
 public function index(): View {
  $profile=Applicant::with(['applications.vacancy.position.department','applications.interviews','applications.statusHistories'])->where('user_id',auth()->id())->firstOrFail();
  $current=$profile->applications->sortByDesc('applied_at')->first();
  $stages=['New Applicant'=>10,'For Screening'=>20,'Shortlisted'=>30,'For Initial Interview'=>45,'Initial Interview Passed'=>55,'For Examination'=>65,'Examination Passed'=>75,'For Final Interview'=>85,'For Job Offer'=>95,'Hired'=>100,'Rejected'=>100,'On Hold'=>50,'Withdrawn'=>100];
  $application=['reference'=>$current->reference_no,'position'=>$current->vacancy->title,'department'=>$current->vacancy->position->department->name,'date_applied'=>$current->applied_at->format('F d, Y'),'status'=>$current->status,'progress'=>$stages[$current->status]??10];
  $timeline=$current->statusHistories->sortBy('created_at')->map(fn($h)=>['title'=>$h->status,'date'=>$h->created_at->format('F d, Y - g:i A'),'state'=>'completed'])->values()->all();
  $timeline[]=['title'=>$current->status,'date'=>'Current stage','state'=>'current'];
  $availableJobs=JobVacancy::with('position.department')->openForApplications()->where('id','!=',$current->job_vacancy_id)->take(5)->get()->map(fn($j)=>['title'=>$j->title,'department'=>$j->position->department->name,'type'=>$j->employment_type,'closing'=>$j->closing_date?->format('F d, Y')??'Open until filled']);
  return view('applicant.dashboard.index',compact('application','timeline','availableJobs','profile'));
 }
}
