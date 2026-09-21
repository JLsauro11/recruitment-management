@php
    $isHr = request()->routeIs('hr.*');
    $prefix = $isHr ? 'hr' : 'admin';
@endphp
<div id="sidebar" class="active">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header"><div class="d-flex justify-content-between align-items-center"><a href="{{ route($prefix.'.dashboard') }}" class="recruitment-brand text-decoration-none">

                    <img src="{{ asset('assets/images/logo/rs8-logo.png') }}"
                         alt="RS8 Logo"
                         class="sidebar-logo">

                    <span>
        <strong>RS8 Recruitment</strong>
        <small>Management System</small>
    </span>

                </a><div class="toggler"><a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a></div></div></div>
        <div class="sidebar-menu"><ul class="menu">
            <li class="sidebar-title">MAIN MENU</li>
            <li class="sidebar-item {{ request()->routeIs($prefix.'.dashboard')?'active':'' }}"><a href="{{ route($prefix.'.dashboard') }}" class="sidebar-link"><i class="bi bi-grid-fill"></i><span>Dashboard</span></a></li>
            <li class="sidebar-title">RECRUITMENT</li>
            <li class="sidebar-item {{ request()->routeIs($prefix.'.applicants.*')?'active':'' }}"><a href="{{ route($prefix.'.applicants.index') }}" class="sidebar-link"><i class="bi bi-people-fill"></i><span>Applicants</span></a></li>
            <li class="sidebar-item {{ request()->routeIs($prefix.'.interviews.*')?'active':'' }}"><a href="{{ route($prefix.'.interviews.index') }}" class="sidebar-link"><i class="bi bi-calendar-event-fill"></i><span>Interviews</span></a></li>
            {{--<li class="sidebar-item"><a href="{{ route('careers.index') }}" target="_blank" class="sidebar-link"><i class="bi bi-box-arrow-up-right"></i><span>Public Application Link</span></a></li>--}}
            <li class="sidebar-item {{ request()->routeIs($prefix.'.hiring-status.*')?'active':'' }}"><a href="{{ route($prefix.'.hiring-status.index') }}" class="sidebar-link"><i class="bi bi-arrow-repeat"></i><span>Hiring Status</span></a></li>
            <li class="sidebar-item {{ request()->routeIs($prefix.'.assessment-insights.*')?'active':'' }}"><a href="{{ route($prefix.'.assessment-insights.index') }}" class="sidebar-link"><i class="bi bi-stars"></i><span>Assessment Insights</span></a></li>
            @if(!$isHr)
                <li class="sidebar-title">JOB MANAGEMENT</li>
                <li class="sidebar-item {{ request()->routeIs('admin.vacancies.*')?'active':'' }}"><a href="{{ route('admin.vacancies.index') }}" class="sidebar-link"><i class="bi bi-megaphone-fill"></i><span>Job Vacancies</span></a></li>
                <li class="sidebar-item {{ request()->routeIs('admin.positions.*')?'active':'' }}"><a href="{{ route('admin.positions.index') }}" class="sidebar-link"><i class="bi bi-person-vcard-fill"></i><span>Positions</span></a></li>
                <li class="sidebar-item {{ request()->routeIs('admin.departments.*')?'active':'' }}"><a href="{{ route('admin.departments.index') }}" class="sidebar-link"><i class="bi bi-building-fill"></i><span>Departments</span></a></li>
                <li class="sidebar-title">ADMINISTRATION</li>
                <li class="sidebar-item {{ request()->routeIs('admin.users.*')?'active':'' }}"><a href="{{ route('admin.users.index') }}" class="sidebar-link"><i class="bi bi-person-gear"></i><span>User Accounts</span></a></li>
            @endif
            <li class="sidebar-title">REPORTS</li>
            <li class="sidebar-item {{ request()->routeIs($prefix.'.reports.*')?'active':'' }}"><a href="{{ route($prefix.'.reports.index') }}" class="sidebar-link"><i class="bi bi-file-earmark-bar-graph-fill"></i><span>Recruitment Reports</span></a></li>
            @if(!$isHr)<li class="sidebar-item {{ request()->routeIs('admin.settings.*')?'active':'' }}"><a href="{{ route('admin.settings.index') }}" class="sidebar-link"><i class="bi bi-gear-fill"></i><span>Settings</span></a></li>@endif
        </ul></div>
        <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
    </div>
</div>
