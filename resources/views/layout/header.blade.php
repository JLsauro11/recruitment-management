@php
    $isHr = request()->routeIs('hr.*');
    $isApplicant = request()->routeIs('applicant.*');
    $displayName = $isHr ? 'HR Officer' : ($isApplicant ? 'Juan Dela Cruz' : 'Administrator');
    $displayRole = $isHr ? 'Recruitment Team' : ($isApplicant ? 'Applicant Account' : 'System Admin');
    $initial = $isHr ? 'H' : ($isApplicant ? 'J' : 'A');
@endphp
<header class="mb-4 admin-topbar">
    <a href="#" class="burger-btn d-block d-xl-none"><i class="bi bi-justify fs-3"></i></a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <button class="btn topbar-icon position-relative" type="button" aria-label="Notifications"><i class="bi bi-bell"></i><span class="notification-dot"></span></button>
        <div class="dropdown">
            <button class="btn admin-profile dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="profile-avatar">{{ $initial }}</span>
                <span class="d-none d-md-inline text-start"><strong class="d-block">{{ $displayName }}</strong><small>{{ $displayRole }}</small></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
