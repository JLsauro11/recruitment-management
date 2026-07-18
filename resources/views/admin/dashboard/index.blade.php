@extends('layout.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')
@section('page-description', 'Monitor applicants, vacancies, interviews, and hiring progress.')

@section('page-actions')
<div class="d-flex gap-2">
    <button class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> Export Report</button>
    <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Job Vacancy</button>
</div>
@endsection

@section('content')
<div class="page-content">
    <section class="welcome-panel mb-4">
        <div>
            <span class="eyebrow">RECRUITMENT OVERVIEW</span>
            <h2>Welcome back, Administrator!</h2>
            <p class="mb-0">Here is the latest summary of your recruitment activities.</p>
        </div>
        <div class="welcome-date">
            <i class="bi bi-calendar3"></i>
            <span>{{ now()->format('F d, Y') }}</span>
        </div>
    </section>

    <section class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Total Applicants', 'value' => $statistics['total_applicants'], 'icon' => 'bi-people-fill', 'class' => 'blue', 'note' => '12% from last month'],
                ['label' => 'New Applicants', 'value' => $statistics['new_applicants'], 'icon' => 'bi-person-plus-fill', 'class' => 'cyan', 'note' => 'Awaiting review'],
                ['label' => 'For Screening', 'value' => $statistics['for_screening'], 'icon' => 'bi-search', 'class' => 'orange', 'note' => 'Qualification check'],
                ['label' => 'Scheduled Interviews', 'value' => $statistics['scheduled_interviews'], 'icon' => 'bi-calendar-check-fill', 'class' => 'purple', 'note' => 'Upcoming interviews'],
                ['label' => 'For Examination', 'value' => $statistics['for_examination'], 'icon' => 'bi-clipboard-data-fill', 'class' => 'yellow', 'note' => 'Pending examination'],
                ['label' => 'Hired Applicants', 'value' => $statistics['hired_applicants'], 'icon' => 'bi-person-check-fill', 'class' => 'green', 'note' => 'Successfully hired'],
                ['label' => 'Rejected Applicants', 'value' => $statistics['rejected_applicants'], 'icon' => 'bi-person-x-fill', 'class' => 'red', 'note' => 'Not selected'],
                ['label' => 'Active Vacancies', 'value' => $statistics['active_vacancies'], 'icon' => 'bi-megaphone-fill', 'class' => 'navy', 'note' => 'Currently open'],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card recruitment-stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <p class="stat-label">{{ $card['label'] }}</p>
                                <h3>{{ number_format($card['value']) }}</h3>
                                <small>{{ $card['note'] }}</small>
                            </div>
                            <div class="stat-icon {{ $card['class'] }}"><i class="bi {{ $card['icon'] }}"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card dashboard-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">Applicant Trends</h4>
                        <p class="text-muted mb-0">Applications received during the last six months</p>
                    </div>
                    <select class="form-select form-select-sm chart-filter">
                        <option>Last 6 months</option>
                        <option>This year</option>
                    </select>
                </div>
                <div class="card-body">
                    <div id="applicantTrendChart"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card dashboard-card h-100">
                <div class="card-header">
                    <h4 class="mb-1">Hiring Status</h4>
                    <p class="text-muted mb-0">Current applicant distribution</p>
                </div>
                <div class="card-body">
                    <div id="hiringStatusChart"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card dashboard-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">Recent Applicants</h4>
                        <p class="text-muted mb-0">Latest submitted applications</p>
                    </div>
                    <a href="#" class="small fw-bold">View All</a>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle recruitment-table">
                            <thead>
                                <tr><th>Applicant</th><th>Position</th><th>Date Applied</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                            @foreach($recentApplicants as $applicant)
                                @php
                                    $statusClass = match($applicant['status']) {
                                        'New Applicant' => 'status-new',
                                        'For Screening' => 'status-screening',
                                        'For Interview' => 'status-interview',
                                        'For Examination' => 'status-exam',
                                        'Hired' => 'status-hired',
                                        default => 'status-default',
                                    };
                                @endphp
                                <tr>
                                    <td><div class="d-flex align-items-center gap-2"><span class="applicant-avatar">{{ strtoupper(substr($applicant['name'], 0, 1)) }}</span><strong>{{ $applicant['name'] }}</strong></div></td>
                                    <td>{{ $applicant['position'] }}</td>
                                    <td>{{ $applicant['date'] }}</td>
                                    <td><span class="status-badge {{ $statusClass }}">{{ $applicant['status'] }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card dashboard-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">Upcoming Interviews</h4>
                        <p class="text-muted mb-0">Next scheduled interviews</p>
                    </div>
                    <a href="#" class="small fw-bold">Calendar</a>
                </div>
                <div class="card-body pt-1">
                    @foreach($upcomingInterviews as $interview)
                        <div class="interview-item">
                            <div class="interview-date"><strong>{{ $interview['date'] }}</strong><small>{{ $interview['time'] }}</small></div>
                            <div><strong>{{ $interview['name'] }}</strong><small>{{ $interview['position'] }}</small></div>
                            <button class="btn btn-sm btn-light"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    new ApexCharts(document.querySelector('#applicantTrendChart'), {
        chart: { type: 'area', height: 310, toolbar: { show: false } },
        series: [{ name: 'Applicants', data: @json($trendValues) }],
        xaxis: { categories: @json($trendLabels) },
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        colors: ['#ed1c24'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .28, opacityTo: .03, stops: [0, 95, 100] } },
        grid: { borderColor: '#eef1f7' }
    }).render();

    new ApexCharts(document.querySelector('#hiringStatusChart'), {
        chart: { type: 'donut', height: 305 },
        series: @json($statusValues),
        labels: @json($statusLabels),
        colors: ['#f59e0b', '#7c3aed', '#06b6d4', '#2563eb', '#16a34a', '#dc2626'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Applicants', formatter: function (w) { return w.globals.seriesTotals.reduce((a,b)=>a+b,0); } } } } } }
    }).render();
});
</script>
@endpush
