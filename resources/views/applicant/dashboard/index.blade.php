@extends('layout.app')

@section('title', 'Applicant Dashboard')
@section('page-title', 'Applicant Dashboard')
@section('page-description', 'Track your job application, interview schedule, and available opportunities.')

@section('page-actions')
<div class="d-flex gap-2"><button class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> Download Application</button><button class="btn btn-primary"><i class="bi bi-briefcase-fill me-1"></i> Browse Jobs</button></div>
@endsection

@section('content')
<div class="page-content">
    <section class="welcome-panel applicant-welcome mb-4"><div><span class="eyebrow">APPLICATION PORTAL</span><h2>Welcome, {{ $profile->full_name }}!</h2><p class="mb-0">Your application is moving forward. Review your next step below.</p></div><div class="welcome-date"><i class="bi bi-hash"></i><span>{{ $application['reference'] }}</span></div></section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-8"><div class="card dashboard-card h-100"><div class="card-header"><h4 class="mb-1">Current Application</h4><p class="text-muted mb-0">{{ $application['position'] }} · {{ $application['department'] }}</p></div><div class="card-body"><div class="application-summary"><div><span class="status-badge status-interview">{{ $application['status'] }}</span><h3 class="mt-3 mb-1">{{ $application['progress'] }}% Complete</h3><p class="text-muted">Applied on {{ $application['date_applied'] }}</p></div><div class="application-progress-circle" style="--progress: {{ $application['progress'] }}"><span>{{ $application['progress'] }}%</span></div></div><div class="progress mt-4" style="height:10px"><div class="progress-bar bg-danger" style="width:{{ $application['progress'] }}%"></div></div></div></div></div>
        <div class="col-12 col-xl-4"><div class="card dashboard-card h-100"><div class="card-header"><h4 class="mb-1">Next Schedule</h4><p class="text-muted mb-0">Initial Interview</p></div><div class="card-body"><div class="next-schedule-card"><div class="schedule-icon"><i class="bi bi-calendar-check-fill"></i></div><h3>July 18, 2026</h3><p class="mb-1">10:30 AM</p><small>RS8 Main Office · HR Department</small><button class="btn btn-primary w-100 mt-4">View Interview Details</button></div></div></div></div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-xl-7"><div class="card dashboard-card"><div class="card-header"><h4 class="mb-1">Application Timeline</h4><p class="text-muted mb-0">Follow every stage of your application</p></div><div class="card-body"><div class="application-timeline">@foreach($timeline as $item)<div class="timeline-step {{ $item['state'] }}"><div class="timeline-marker"><i class="bi {{ $item['state']==='completed' ? 'bi-check-lg' : ($item['state']==='current' ? 'bi-hourglass-split' : 'bi-circle') }}"></i></div><div><strong>{{ $item['title'] }}</strong><small>{{ $item['date'] }}</small></div></div>@endforeach</div></div></div></div>
        <div class="col-12 col-xl-5"><div class="card dashboard-card"><div class="card-header d-flex justify-content-between align-items-center"><div><h4 class="mb-1">Other Available Jobs</h4><p class="text-muted mb-0">You may also explore these openings</p></div><a href="#" class="small fw-bold">View All</a></div><div class="card-body pt-1">@foreach($availableJobs as $job)<div class="job-list-item"><div class="job-icon"><i class="bi bi-briefcase-fill"></i></div><div><strong>{{ $job['title'] }}</strong><small>{{ $job['department'] }} · {{ $job['type'] }}</small><span>Closes {{ $job['closing'] }}</span></div><button class="btn btn-sm btn-light"><i class="bi bi-chevron-right"></i></button></div>@endforeach</div></div></div>
    </section>
</div>
@endsection
