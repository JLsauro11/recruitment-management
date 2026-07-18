@php
    $isAdmin = request()->routeIs('admin.*');
    $isHr = request()->routeIs('hr.*');
    $isApplicant = request()->routeIs('applicant.*');
    $homeRoute = $isHr ? 'hr.dashboard' : ($isApplicant ? 'applicant.dashboard' : 'admin.dashboard');
@endphp

<div id="sidebar" class="active">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route($homeRoute) }}" class="recruitment-brand text-decoration-none">
                    <span class="brand-mark">R</span>
                    <span><strong>RS8 Recruitment</strong><small>Management System</small></span>
                </a>
                <div class="toggler"><a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a></div>
            </div>
        </div>

        <div class="sidebar-menu">
            <ul class="menu">
                <li class="sidebar-title">MAIN MENU</li>
                @if($isAdmin)
                    <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
                            <i class="bi bi-grid-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                @elseif($isHr)
                    <li class="sidebar-item {{ request()->routeIs('hr.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('hr.dashboard') }}" class="sidebar-link">
                            <i class="bi bi-grid-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                @else
                    <li class="sidebar-item {{ request()->routeIs('applicant.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('applicant.dashboard') }}" class="sidebar-link">
                            <i class="bi bi-grid-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                @endif

                @if($isAdmin)
                    <li class="sidebar-title">RECRUITMENT</li>
                    <li class="sidebar-item {{ request()->routeIs('admin.applicants.*')?'active':'' }}"><a href="{{ route('admin.applicants.index') }}" class="sidebar-link"><i class="bi bi-people-fill"></i><span>Applicants</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-calendar-event-fill"></i><span>Interviews</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i><span>Exam Results</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-arrow-repeat"></i><span>Hiring Status</span></a></li>
                    <li class="sidebar-title">JOB MANAGEMENT</li>
                    <li class="sidebar-item {{ request()->routeIs('admin.vacancies.*')?'active':'' }}"><a href="{{ route('admin.vacancies.index') }}" class="sidebar-link"><i class="bi bi-megaphone-fill"></i><span>Job Vacancies</span></a></li>
                    <li class="sidebar-item {{ request()->routeIs('admin.positions.*')?'active':'' }}"><a href="{{ route('admin.positions.index') }}" class="sidebar-link"><i class="bi bi-person-vcard-fill"></i><span>Positions</span></a></li>
                    <li class="sidebar-item {{ request()->routeIs('admin.departments.*')?'active':'' }}"><a href="{{ route('admin.departments.index') }}" class="sidebar-link"><i class="bi bi-building-fill"></i><span>Departments</span></a></li>
                    <li class="sidebar-title">ADMINISTRATION</li>
                    <li class="sidebar-item {{ request()->routeIs('admin.users.*')?'active':'' }}"><a href="{{ route('admin.users.index') }}" class="sidebar-link"><i class="bi bi-person-gear"></i><span>User Accounts</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-file-earmark-bar-graph-fill"></i><span>Reports</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-gear-fill"></i><span>Settings</span></a></li>
                @elseif($isHr)
                    <li class="sidebar-title">RECRUITMENT WORKSPACE</li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-people-fill"></i><span>Applicant Pool</span><span class="badge bg-danger ms-auto">18</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-funnel-fill"></i><span>Screening Queue</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-calendar-event-fill"></i><span>Interview Calendar</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i><span>Exam Results</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-arrow-repeat"></i><span>Hiring Status</span></a></li>
                    <li class="sidebar-title">JOB MANAGEMENT</li>
                    <li class="sidebar-item {{ request()->routeIs('admin.vacancies.*')?'active':'' }}"><a href="{{ route('admin.vacancies.index') }}" class="sidebar-link"><i class="bi bi-megaphone-fill"></i><span>Job Vacancies</span></a></li>
                    <li class="sidebar-title">REPORTS</li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-file-earmark-bar-graph-fill"></i><span>Recruitment Reports</span></a></li>
                @else
                    <li class="sidebar-title">MY APPLICATION</li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-file-earmark-person-fill"></i><span>Application Details</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-clock-history"></i><span>Status Timeline</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i><span>Interview Schedule</span></a></li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-folder-fill"></i><span>My Documents</span></a></li>
                    <li class="sidebar-title">OPPORTUNITIES</li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-briefcase-fill"></i><span>Available Jobs</span></a></li>
                    <li class="sidebar-title">ACCOUNT</li>
                    <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="bi bi-person-circle"></i><span>My Profile</span></a></li>
                @endif
            </ul>
        </div>

        <div class="sidebar-footer-card">
            <div class="profile-avatar">{{ $isHr ? 'H' : ($isApplicant ? 'J' : 'A') }}</div>
            <div>
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ ucfirst(auth()->user()->role) }}</small>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-danger w-100 btn-sm">Logout</button>
        </form>
        <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
    </div>
</div>
