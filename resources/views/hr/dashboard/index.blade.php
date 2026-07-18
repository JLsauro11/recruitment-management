@extends('layout.app')

@section('title', 'HR Dashboard')
@section('page-title', 'HR Dashboard')
@section('page-description', 'Manage daily recruitment tasks, interviews, and applicant progress.')

@section('page-actions')
<div class="d-flex gap-2"><button class="btn btn-outline-secondary"><i class="bi bi-calendar3 me-1"></i> View Calendar</button><button class="btn btn-primary"><i class="bi bi-person-plus-fill me-1"></i> Add Applicant</button></div>
@endsection

@section('content')
<div class="page-content">
    <section class="welcome-panel mb-4"><div><span class="eyebrow">HR RECRUITMENT WORKSPACE</span><h2>Good day, HR Officer!</h2><p class="mb-0">Review priority applicants and complete today's recruitment activities.</p></div><div class="welcome-date"><i class="bi bi-calendar3"></i><span>{{ now()->format('F d, Y') }}</span></div></section>

    <section class="row g-3 mb-4">
        @php $cards = [
            ['New Applicants',$statistics['new_applicants'],'bi-person-plus-fill','cyan','Awaiting review'],
            ['For Screening',$statistics['for_screening'],'bi-funnel-fill','orange','Needs qualification check'],
            ["Today's Interviews",$statistics['today_interviews'],'bi-calendar-check-fill','purple','Scheduled today'],
            ['Pending Results',$statistics['pending_results'],'bi-clipboard-data-fill','yellow','For evaluation'],
            ['For Job Offer',$statistics['for_job_offer'],'bi-envelope-check-fill','green','Ready for offer'],
            ['Active Vacancies',$statistics['active_vacancies'],'bi-megaphone-fill','navy','Currently open'],
        ]; @endphp
        @foreach($cards as $card)<div class="col-12 col-sm-6 col-xl-4"><div class="card recruitment-stat-card h-100"><div class="card-body"><div class="d-flex align-items-start justify-content-between"><div><p class="stat-label">{{ $card[0] }}</p><h3>{{ $card[1] }}</h3><small>{{ $card[4] }}</small></div><div class="stat-icon {{ $card[3] }}"><i class="bi {{ $card[2] }}"></i></div></div></div></div></div>@endforeach
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-8"><div class="card dashboard-card h-100"><div class="card-header"><h4 class="mb-1">Weekly Recruitment Activity</h4><p class="text-muted mb-0">Applications reviewed and interviews completed</p></div><div class="card-body"><div id="hrActivityChart"></div></div></div></div>
        <div class="col-12 col-xl-4"><div class="card dashboard-card h-100"><div class="card-header"><h4 class="mb-1">Today's Schedule</h4><p class="text-muted mb-0">Your upcoming interviews</p></div><div class="card-body pt-1">@foreach($todaySchedule as $schedule)<div class="interview-item"><div class="interview-date"><strong>{{ $schedule['time'] }}</strong><small>Today</small></div><div><strong>{{ $schedule['name'] }}</strong><small>{{ $schedule['type'] }}</small></div><button class="btn btn-sm btn-light"><i class="bi bi-chevron-right"></i></button></div>@endforeach</div></div></div>
    </section>

    <section class="card dashboard-card"><div class="card-header d-flex justify-content-between align-items-center"><div><h4 class="mb-1">Priority Applicant Queue</h4><p class="text-muted mb-0">Applicants requiring immediate HR action</p></div><a href="#" class="small fw-bold">View Applicant Pool</a></div><div class="card-body pt-0"><div class="table-responsive"><table class="table align-middle recruitment-table"><thead><tr><th>Applicant</th><th>Position</th><th>Current Stage</th><th>Last Updated</th><th>Action</th></tr></thead><tbody>@foreach($priorityApplicants as $applicant)<tr><td><div class="d-flex align-items-center gap-2"><span class="applicant-avatar">{{ strtoupper(substr($applicant['name'],0,1)) }}</span><strong>{{ $applicant['name'] }}</strong></div></td><td>{{ $applicant['position'] }}</td><td><span class="status-badge status-screening">{{ $applicant['stage'] }}</span></td><td>{{ $applicant['updated'] }}</td><td><button class="btn btn-sm btn-primary">Review</button></td></tr>@endforeach</tbody></table></div></div></section>
</div>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded',function(){new ApexCharts(document.querySelector('#hrActivityChart'),{chart:{type:'bar',height:320,toolbar:{show:false}},series:[{name:'Reviewed',data:[8,12,10,15,13,18,11]},{name:'Interviews',data:[2,4,3,5,4,6,4]}],xaxis:{categories:['Mon','Tue','Wed','Thu','Fri','Sat','Sun']},colors:['#ed1c24','#1f2937'],plotOptions:{bar:{borderRadius:5,columnWidth:'45%'}},dataLabels:{enabled:false},grid:{borderColor:'#eef1f7'}}).render();});</script>
@endpush
