@extends('layout.applicant')

@section('title', 'Careers | RS8 Recruitment')
@section('body_class', 'careers-light careers-premium')

@section('content')
    @php
        $departmentCount = $vacancies
            ->pluck('position.department.name')
            ->filter()
            ->unique()
            ->count();

        $departments = $vacancies
            ->pluck('position.department.name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $jobDetailsPayload = $vacancies->mapWithKeys(function ($vacancy) {
            $department = $vacancy->position?->department?->name ?? 'RS8 Team';

            return [(string) $vacancy->id => [
                'id' => $vacancy->id,
                'title' => $vacancy->title,
                'position' => $vacancy->position?->name ?? $vacancy->title,
                'department' => $department,
                'employment_type' => $vacancy->employment_type,
                'slots' => $vacancy->slots,
                'opening_date' => optional($vacancy->opening_date)->format('M d, Y'),
                'closing_date' => $vacancy->closing_date ? $vacancy->closing_date->format('M d, Y') : 'Open until filled',
                'salary_min' => $vacancy->salary_min,
                'salary_max' => $vacancy->salary_max,
                'qualifications' => $vacancy->qualifications,
                'poster_url' => $vacancy->poster_path ? asset('storage/'.$vacancy->poster_path) : null,
                'apply_url' => route('careers.select', $vacancy),
            ]];
        });
    @endphp

    <div class="career-site">
        @if(session('career_error'))
            <div class="career-shell career-alert-wrap">
                <div class="career-alert" role="alert">
                    <span class="career-alert-icon"><i class="bi bi-exclamation-circle-fill"></i></span>
                    <span>{{ session('career_error') }}</span>
                </div>
            </div>
        @endif

        <section class="career-hero">
            <div class="career-hero-pattern" aria-hidden="true"></div>
            <div class="career-shell career-hero-grid">
                <div class="career-hero-copy">
                    <div class="career-eyebrow">
                        <span class="career-eyebrow-line"></span>
                        Careers at RS8
                    </div>

                    <h1>
                        Finds work that
                        <span>moves you forward.</span>
                    </h1>

                    <p class="career-hero-lead">
                        Build your career with Redspeed Motoworkz OPC. Join a performance-driven team where
                        creativity, precision, technology, and real-world execution come together.
                    </p>

                    <div class="career-hero-actions">
                        <a href="#open-positions" class="career-btn career-btn-primary">
                            Explore open roles
                            <i class="bi bi-arrow-down-right"></i>
                        </a>
                        <a href="#life-at-rs8" class="career-link">
                            Discover life at RS8
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <div class="career-hero-stats" aria-label="Recruitment summary">
                        <div class="career-stat">
                            <strong>{{ $vacancies->count() }}</strong>
                            <span>Open {{ \Illuminate\Support\Str::plural('position', $vacancies->count()) }}</span>
                        </div>
                        <div class="career-stat">
                            <strong>{{ $departmentCount }}</strong>
                            <span>{{ \Illuminate\Support\Str::plural('department', $departmentCount) }}</span>
                        </div>
                        <div class="career-stat">
                            <strong>1</strong>
                            <span>Guided application</span>
                        </div>
                    </div>
                </div>

                <div class="career-hero-visual" aria-hidden="true">
                    <div class="career-hero-artwork">
                        <img
                            src="{{ asset('assets/images/careers/hero-hiring-premium.png') }}"
                            alt=""
                            class="career-hero-illustration"
                            loading="eager"
                        >
                    </div>
                </div>
            </div>
        </section>

        <section class="career-value-strip" aria-label="RS8 career benefits">
            <div class="career-shell career-value-strip-inner">
                <div class="career-value-item">
                    <span class="career-value-icon"><i class="bi bi-speedometer2"></i></span>
                    <span>Performance-driven teams</span>
                </div>
                <div class="career-value-item">
                    <span class="career-value-icon"><i class="bi bi-graph-up"></i></span>
                    <span>Real growth opportunities</span>
                </div>
                <div class="career-value-item">
                    <span class="career-value-icon"><i class="bi bi-people-fill"></i></span>
                    <span>Collaborative work culture</span>
                </div>
                <div class="career-value-item">
                    <span class="career-value-icon"><i class="bi bi-lightning-fill"></i></span>
                    <span>Fast-moving industry</span>
                </div>
            </div>
        </section>

        <section class="jobs-section" id="open-positions">
            <div class="career-shell">
                <div class="section-heading section-heading-jobs">
                    <div>
                        <div class="career-eyebrow">
                            <span class="career-eyebrow-line"></span>
                            Open positions
                        </div>
                        <h2>Find where you fit.</h2>
                    </div>
                    <p>
                        Search current opportunities and choose the role you want. Once selected, that vacancy is
                        securely attached to your application so you can continue directly to the employment form.
                    </p>
                </div>

                @if($vacancies->isNotEmpty())
                    <div class="jobs-control-panel">
                        <label class="jobs-search" for="jobSearch">
                            <i class="bi bi-search"></i>
                            <input
                                id="jobSearch"
                                type="search"
                                placeholder="Search by position, department, or keyword"
                                autocomplete="off"
                            >
                            <button type="button" id="clearSearch" class="jobs-clear" aria-label="Clear search">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </label>

                        <div class="jobs-filter" id="departmentFilterWrap">
                            <span class="jobs-filter-label">Department</span>
                            <select id="departmentFilter" class="jobs-filter-native" tabindex="-1" aria-hidden="true">
                                <option value="">All departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ \Illuminate\Support\Str::lower($department) }}">{{ $department }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="jobs-filter-trigger" id="departmentFilterTrigger" aria-haspopup="listbox" aria-expanded="false">
                                <span id="departmentFilterText">All departments</span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                            <div class="jobs-filter-menu" id="departmentFilterMenu" role="listbox" aria-label="Filter by department">
                                <button type="button" class="jobs-filter-option is-selected" data-value="" role="option" aria-selected="true">
                                    <span>All departments</span>
                                    <i class="bi bi-check2"></i>
                                </button>
                                @foreach($departments as $department)
                                    <button type="button" class="jobs-filter-option" data-value="{{ \Illuminate\Support\Str::lower($department) }}" role="option" aria-selected="false">
                                        <span>{{ $department }}</span>
                                        <i class="bi bi-check2"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="jobs-meta-row">
                        <span><strong id="visibleRoleCount">{{ $vacancies->count() }}</strong> opportunities available</span>
                        <span>Latest openings appear first</span>
                    </div>

                    <div class="jobs-list" id="jobList">
                        @foreach($vacancies as $vacancy)
                            @php
                                $department = $vacancy->position?->department?->name ?? 'RS8 Team';
                                $searchText = \Illuminate\Support\Str::lower(
                                    $vacancy->title.' '.$department.' '.$vacancy->employment_type.' '.($vacancy->qualifications ?? '')
                                );
                            @endphp

                            <article
                                class="job-entry"
                                data-job-row
                                data-search="{{ $searchText }}"
                                data-department="{{ \Illuminate\Support\Str::lower($department) }}"
                            >
                                <button
                                    type="button"
                                    class="job-row"
                                    data-job-details="{{ $vacancy->id }}"
                                    aria-label="View details for {{ $vacancy->title }}"
                                >
                                    <span class="job-poster">
                                        @if($vacancy->poster_path)
                                            <img
                                                src="{{ asset('storage/'.$vacancy->poster_path) }}"
                                                alt="{{ $vacancy->title }} job poster"
                                                loading="lazy"
                                            >
                                        @else
                                            <span class="job-poster-fallback">
                                                <i class="bi bi-briefcase-fill"></i>
                                            </span>
                                        @endif
                                    </span>

                                    <span class="job-main">
                                        <span class="job-kicker">{{ $department }}</span>
                                        <span class="job-title">{{ $vacancy->title }}</span>
                                        <span class="job-list-hint">View full qualifications and vacancy details.</span>
                                    </span>

                                    <span class="job-details">
                                        <span class="job-detail-row">
                                            <i class="bi bi-clock"></i>
                                            <span class="job-detail-label">{{ $vacancy->employment_type }}</span>
                                        </span>
                                        <span class="job-detail-row">
                                            <i class="bi bi-people"></i>
                                            <span class="job-detail-label">{{ $vacancy->slots }} {{ \Illuminate\Support\Str::plural('slot', $vacancy->slots) }}</span>
                                        </span>
                                        <span class="job-detail-row">
                                            <i class="bi bi-calendar2-check"></i>
                                            <span class="job-detail-label">
                                                @if($vacancy->closing_date)
                                                    Until {{ $vacancy->closing_date->format('M d, Y') }}
                                                @else
                                                    Open until filled
                                                @endif
                                            </span>
                                        </span>
                                    </span>

                                    <span class="job-action">
                                        <span>View details</span>
                                        <i class="bi bi-arrow-up-right"></i>
                                    </span>
                                </button>
                            </article>
                        @endforeach
                    </div>

                    <div class="jobs-empty-filter" id="jobsEmptyFilter" hidden>
                        <span class="jobs-empty-icon"><i class="bi bi-search"></i></span>
                        <div>
                            <strong>No matching roles found.</strong>
                            <p>Try another keyword or choose a different department.</p>
                        </div>
                    </div>
                @else
                    <div class="jobs-no-openings">
                        <span class="jobs-empty-icon"><i class="bi bi-briefcase"></i></span>
                        <div>
                            <h3>No open positions right now.</h3>
                            <p>Please check back soon for new opportunities at RS8.</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>


        <div class="modal fade career-job-modal" id="jobDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content career-job-modal-content">
                    <button type="button" class="career-job-modal-close" data-bs-dismiss="modal" aria-label="Close job details">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <div class="career-job-detail-grid">
                        <aside class="career-job-detail-poster-wrap">
                            <div class="career-job-detail-poster" id="jobDetailPosterWrap">
                                <img id="jobDetailPoster" alt="Selected job poster">
                                <div class="career-job-detail-poster-fallback" id="jobDetailPosterFallback">
                                    <i class="bi bi-briefcase-fill"></i>
                                    <span>RS8 Careers</span>
                                </div>
                            </div>
                        </aside>

                        <div class="career-job-detail-content">
                            <div class="career-job-detail-eyebrow" id="jobDetailDepartment"></div>
                            <h2 id="jobDetailTitle"></h2>
                            <p class="career-job-detail-intro">Review the vacancy details and qualifications before continuing to the application form.</p>

                            <div class="career-job-meta-grid">
                                <div class="career-job-meta-item">
                                    <span class="career-job-meta-icon"><i class="bi bi-clock"></i></span>
                                    <span><small>Employment</small><strong id="jobDetailEmployment"></strong></span>
                                </div>
                                <div class="career-job-meta-item">
                                    <span class="career-job-meta-icon"><i class="bi bi-people"></i></span>
                                    <span><small>Available slots</small><strong id="jobDetailSlots"></strong></span>
                                </div>
                                <div class="career-job-meta-item">
                                    <span class="career-job-meta-icon"><i class="bi bi-calendar-event"></i></span>
                                    <span><small>Opening date</small><strong id="jobDetailOpening"></strong></span>
                                </div>
                                <div class="career-job-meta-item">
                                    <span class="career-job-meta-icon"><i class="bi bi-calendar2-check"></i></span>
                                    <span><small>Closing date</small><strong id="jobDetailClosing"></strong></span>
                                </div>
                                <div class="career-job-meta-item">
                                    <span class="career-job-meta-icon"><i class="bi bi-building"></i></span>
                                    <span><small>Department</small><strong id="jobDetailDepartmentMeta"></strong></span>
                                </div>
                                <div class="career-job-meta-item">
                                    <span class="career-job-meta-icon"><i class="bi bi-cash-stack"></i></span>
                                    <span><small>Salary</small><strong id="jobDetailSalary"></strong></span>
                                </div>
                            </div>

                            <section class="career-job-qualifications">
                                <div class="career-job-section-heading">
                                    <span><i class="bi bi-patch-check-fill"></i></span>
                                    <div>
                                        <h3>Qualifications</h3>
                                        <p>What we are looking for in this role.</p>
                                    </div>
                                </div>
                                <ul id="jobDetailQualifications"></ul>
                            </section>

                            <div class="career-job-detail-actions">
                                <button type="button" class="career-job-secondary-btn" data-bs-dismiss="modal">Back to openings</button>
                                <form id="jobDetailsApplyForm" method="POST">
                                    @csrf
                                    <button type="submit" class="career-job-apply-btn">
                                        Apply now
                                        <i class="bi bi-arrow-right"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="career-life" id="life-at-rs8">
            <div class="career-shell career-life-grid">
                <div class="career-life-copy">
                    <div class="career-eyebrow">
                        <span class="career-eyebrow-line"></span>
                        Life at RS8
                    </div>
                    <h2>Grow with a team that values action, learning, and results.</h2>
                    <p class="career-life-lead">
                        Whether your role is creative, technical, operational, or commercial, your work contributes
                        directly to how the business serves riders, customers, and partners.
                    </p>

                    <div class="career-principles">
                        <div class="career-principle">
                            <span class="principle-number">01</span>
                            <div>
                                <h3>Performance with purpose</h3>
                                <p>Take ownership, improve the process, and focus on work that creates measurable impact.</p>
                            </div>
                        </div>
                        <div class="career-principle">
                            <span class="principle-number">02</span>
                            <div>
                                <h3>Growth through real experience</h3>
                                <p>Develop practical skills while solving actual business and customer challenges.</p>
                            </div>
                        </div>
                        <div class="career-principle">
                            <span class="principle-number">03</span>
                            <div>
                                <h3>One team, shared momentum</h3>
                                <p>Collaborate across departments and help one another execute better and faster.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="career-life-visual" aria-hidden="true">
                    <span class="career-life-accent"></span>
                    <img
                        src="{{ asset('assets/images/careers/culture-team-premium.png') }}"
                        alt=""
                        loading="lazy"
                    >
                    <div class="career-life-note">
                        <span>RS8 culture</span>
                        <strong>Learn. Build. Improve.</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="career-process">
            <div class="career-shell career-process-grid">
                <div class="career-process-visual" aria-hidden="true">
                    <img
                        src="{{ asset('assets/images/careers/application-process-premium.png') }}"
                        alt=""
                        loading="lazy"
                    >
                </div>

                <div class="career-process-copy">
                    <div class="career-eyebrow">
                        <span class="career-eyebrow-line"></span>
                        Application process
                    </div>
                    <h2>A simple path from vacancy to application.</h2>
                    <p class="career-process-lead">
                        Your application is tied to the exact role you selected from this careers page.
                    </p>

                    <div class="career-steps">
                        <div class="career-step">
                            <span class="career-step-number">1</span>
                            <div>
                                <strong>Choose your role</strong>
                                <p>Browse current openings and select the position that fits you.</p>
                            </div>
                        </div>
                        <div class="career-step">
                            <span class="career-step-number">2</span>
                            <div>
                                <strong>Complete your application</strong>
                                <p>Provide your employment information and required documents.</p>
                            </div>
                        </div>
                        <div class="career-step">
                            <span class="career-step-number">3</span>
                            <div>
                                <strong>Submit for review</strong>
                                <p>Our recruitment team receives your application for the selected position.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="career-final">
            <div class="career-shell career-final-inner">
                <div>
                    <span class="career-final-kicker">Your next move</span>
                    <h2>Ready to build your career with RS8?</h2>
                    <p>Explore the latest vacancies and choose the opportunity that matches your skills and goals.</p>
                </div>
                <a href="#open-positions" class="career-btn career-btn-white">
                    View open positions
                    <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>
        </section>

        <div
            class="staff-login-overlay"
            id="staffLoginOverlay"
            aria-hidden="true"
            data-auto-open="{{ request()->boolean('staff_login') ? 'true' : 'false' }}"
        >
            <button type="button" class="staff-login-backdrop" data-staff-login-close aria-label="Close staff login"></button>

            <section class="staff-login-dialog" role="dialog" aria-modal="true" aria-labelledby="staffLoginTitle">
                <button type="button" class="staff-login-close" data-staff-login-close aria-label="Close staff login">
                    <span aria-hidden="true">&times;</span>
                </button>

                <div class="staff-login-showcase">
                    <img src="{{ asset('assets/images/logo/rs8-public-logo.png') }}" alt="RS8 Taiwan Speed Factory" class="staff-login-logo">
                    <span class="staff-login-kicker">RS8 recruitment staff portal</span>
                    <h2>Manage hiring in one focused workspace.</h2>
                    <p>Access vacancies, applicants, interviews, and hiring status from the RS8 recruitment system.</p>

                    <div class="staff-login-trust-row" aria-hidden="true">
                        <span><i class="bi bi-shield-check"></i> Secure access</span>
                        <span><i class="bi bi-people"></i> Recruitment team</span>
                    </div>
                </div>

                <div class="staff-login-form-panel">
                    <div class="staff-login-form-wrap">
                        <span class="staff-secure-chip"><i class="bi bi-lock-fill"></i> Staff only</span>
                        <h2 id="staffLoginTitle">Welcome back</h2>
                        <p>Enter your staff credentials to continue.</p>

                        <div class="staff-login-alert" id="staffLoginAlert" role="alert" hidden></div>

                        <form id="staffLoginForm" method="POST" action="{{ route('login.attempt') }}" novalidate>
                            @csrf
                            <div class="staff-login-field">
                                <label for="staffEmail">Email address</label>
                                <span class="staff-input-shell">
                                    <i class="bi bi-envelope"></i>
                                    <input id="staffEmail" type="email" name="email" placeholder="Enter your email address" autocomplete="email" required>
                                </span>
                                <small class="staff-field-error" data-error-for="email"></small>
                            </div>

                            <div class="staff-login-field">
                                <label for="staffPassword">Password</label>
                                <span class="staff-input-shell">
                                    <i class="bi bi-lock"></i>
                                    <input id="staffPassword" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                                    <button type="button" class="staff-password-toggle" id="staffPasswordToggle" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </span>
                                <small class="staff-field-error" data-error-for="password"></small>
                            </div>

                            <button class="staff-login-submit" id="staffLoginSubmit" type="submit">
                                <span>Sign in</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </form>

                        <button type="button" class="staff-back-careers" data-staff-login-close>
                            <i class="bi bi-arrow-left"></i>
                            Back to careers
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');

    .career-site {
        --career-red: #ed1c24;
        --career-red-dark: #c7141b;
        --career-navy: #1d2e59;
        --career-ink: #161b27;
        --career-muted: #667085;
        --career-line: #e8ebf1;
        --career-soft: #f7f8fb;
        --career-cream: #fff8f3;
        --career-blue-soft: #eef4ff;
        overflow: hidden;
        color: var(--career-ink);
        background: #fff;
        font-family: 'Manrope', Nunito, Arial, sans-serif;
        font-size: 16px;
    }

    .careers-premium .app-header {
        border-bottom-color: #edf0f5;
        box-shadow: 0 10px 32px rgba(29,46,89,.055);
    }

    .careers-premium .app-header .container {
        max-width: 1320px;
    }

    .careers-premium .brand-logo {
        width: 214px;
        height: 62px;
    }

    .careers-premium .brand-copy strong {
        font-size: 18px;
        font-weight: 800;
    }

    .careers-premium .brand-copy small {
        font-size: 11px;
    }

    .careers-premium .staff-login-btn {
        min-height: 40px;
        padding-inline: 17px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 800;
    }

    .career-shell {
        width: min(1280px, calc(100% - 56px));
        margin-inline: auto;
    }

    .career-alert-wrap { padding-top: 20px; }

    .career-alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 18px;
        border: 1px solid #ffd2d5;
        border-radius: 14px;
        color: #8d1117;
        background: #fff2f3;
        font-size: 15px;
        font-weight: 700;
    }

    .career-alert-icon {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        flex: 0 0 34px;
        border-radius: 50%;
        color: #fff;
        background: var(--career-red);
    }

    .career-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        color: var(--career-red);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .18em;
        text-transform: uppercase;
    }

    .career-eyebrow-line {
        width: 36px;
        height: 2px;
        border-radius: 999px;
        background: currentColor;
    }

    .career-hero {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at 92% 6%, rgba(237,28,36,.08), transparent 28%),
            radial-gradient(circle at 12% 90%, rgba(63,124,255,.07), transparent 26%),
            linear-gradient(120deg, #fff 0%, #fffaf8 49%, #f8faff 100%);
    }

    .career-hero-pattern {
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: .44;
        background-image: radial-gradient(rgba(30,46,89,.08) 1px, transparent 1px);
        background-size: 24px 24px;
        mask-image: linear-gradient(90deg, transparent 0%, #000 25%, #000 75%, transparent 100%);
    }

    .career-hero-grid {
        position: relative;
        z-index: 2;
        min-height: 720px;
        display: grid;
        grid-template-columns: minmax(0, .93fr) minmax(500px, 1.07fr);
        gap: 56px;
        align-items: center;
        padding-block: 78px 88px;
    }

    .career-hero-copy h1 {
        max-width: 700px;
        margin: 22px 0 0;
        color: var(--career-ink);
        font-size: clamp(58px, 5.4vw, 84px);
        font-weight: 800;
        line-height: .98;
        letter-spacing: -.055em;
    }

    .career-hero-copy h1 span {
        display: block;
        color: var(--career-red);
    }

    .career-hero-lead {
        max-width: 660px;
        margin: 28px 0 0;
        color: var(--career-muted);
        font-size: 18px;
        line-height: 1.82;
    }

    .career-hero-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 24px;
        margin-top: 34px;
    }

    .career-btn {
        min-height: 56px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 0 22px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 800;
        text-decoration: none;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease, color .2s ease;
    }

    .career-btn-primary {
        color: #fff;
        background: var(--career-red);
        box-shadow: 0 16px 34px rgba(237,28,36,.2);
    }

    .career-btn-primary:hover {
        color: #fff;
        background: var(--career-red-dark);
        box-shadow: 0 20px 42px rgba(237,28,36,.27);
        transform: translateY(-2px);
    }

    .career-link {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: #263247;
        font-size: 15px;
        font-weight: 800;
        text-decoration: none;
    }

    .career-link i { color: var(--career-red); transition: transform .2s ease; }
    .career-link:hover { color: var(--career-red); }
    .career-link:hover i { transform: translateX(4px); }

    .career-btn i,
    .career-link i {
        width: 18px;
        height: 18px;
        display: inline-grid;
        place-items: center;
        flex: 0 0 18px;
        line-height: 1;
    }

    .career-hero-stats {
        display: flex;
        align-items: stretch;
        gap: 28px;
        margin-top: 54px;
    }

    .career-stat {
        position: relative;
        min-width: 122px;
        padding-right: 28px;
    }

    .career-stat:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 3px;
        right: 0;
        width: 1px;
        height: 46px;
        background: #dfe3eb;
    }

    .career-stat strong,
    .career-stat span { display: block; }

    .career-stat strong {
        color: #1a2234;
        font-size: 30px;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -.04em;
    }

    .career-stat span {
        margin-top: 7px;
        color: #7c8493;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .045em;
        text-transform: uppercase;
    }

    .career-hero-visual {
        position: relative;
        min-height: 560px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 22px 10px;
    }

    .career-hero-artwork {
        position: relative;
        width: min(100%, 610px);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-inline: auto;
        isolation: isolate;
    }

    .career-hero-artwork::before {
        content: '';
        position: absolute;
        width: 78%;
        aspect-ratio: 1;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(237,28,36,.055) 0%, rgba(237,28,36,.018) 48%, transparent 70%);
        z-index: -1;
        pointer-events: none;
    }

    .career-hero-illustration {
        display: block;
        width: 100%;
        max-width: 610px;
        max-height: 520px;
        object-fit: contain;
        object-position: center;
        margin: 0 auto;
        filter: drop-shadow(0 22px 28px rgba(30, 46, 89, .07));
    }

    .career-value-strip {
        position: relative;
        z-index: 3;
        border-top: 1px solid #eef0f4;
        border-bottom: 1px solid #eef0f4;
        background: rgba(255,255,255,.98);
    }

    .career-value-strip-inner {
        min-height: 86px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        align-items: stretch;
    }

    .career-value-item {
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 18px 22px;
        color: #4d576b;
        font-size: 13px;
        font-weight: 800;
        text-align: center;
        line-height: 1.35;
    }

    .career-value-item:not(:last-child) {
        border-right: 1px solid #eef0f4;
    }

    .career-value-icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        display: inline-grid;
        place-items: center;
        border-radius: 10px;
        color: var(--career-red);
        background: rgba(237,28,36,.075);
    }

    .career-value-icon i {
        display: block;
        margin: 0;
        padding: 0;
        font-size: 16px;
        line-height: 1;
    }

    .career-value-item > span:last-child {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
    }

    .jobs-section {
        position: relative;
        padding: 116px 0 124px;
        background: #fff;
    }

    .jobs-section::before {
        content: '';
        position: absolute;
        width: 360px;
        height: 360px;
        left: -220px;
        top: 150px;
        border: 58px solid rgba(63,124,255,.035);
        border-radius: 50%;
        pointer-events: none;
    }

    .section-heading {
        display: grid;
        grid-template-columns: minmax(0, .92fr) minmax(380px, .72fr);
        gap: 88px;
        align-items: end;
    }

    .section-heading h2,
    .career-life-copy h2,
    .career-process-copy h2,
    .career-final h2 {
        margin: 15px 0 0;
        color: var(--career-navy);
        font-size: clamp(42px, 4vw, 60px);
        font-weight: 800;
        line-height: 1.06;
        letter-spacing: -.048em;
    }

    .section-heading > p {
        max-width: 540px;
        margin: 0 0 2px;
        color: var(--career-muted);
        font-size: 16px;
        line-height: 1.78;
    }

    .jobs-control-panel {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 310px;
        gap: 12px;
        margin-top: 54px;
        padding: 10px;
        border: 1px solid var(--career-line);
        border-radius: 18px;
        background: #f8f9fc;
        box-shadow: 0 14px 42px rgba(29,46,89,.05);
    }

    .jobs-search {
        min-height: 58px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0 16px;
        border-radius: 12px;
        background: #fff;
    }

    .jobs-search > i { color: #8b94a5; font-size: 18px; }

    .jobs-search input {
        width: 100%;
        min-width: 0;
        border: 0;
        outline: 0;
        color: #202a3e;
        background: transparent;
        font: inherit;
        font-size: 15px;
    }

    .jobs-search input::placeholder { color: #9ca4b3; }
    .jobs-search input::-webkit-search-cancel-button,
    .jobs-search input::-webkit-search-decoration { -webkit-appearance: none; appearance: none; display: none; }
    .jobs-search input::-ms-clear,
    .jobs-search input::-ms-reveal { display: none; width: 0; height: 0; }

    .jobs-clear {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        flex: 0 0 34px;
        padding: 0;
        border: 0 !important;
        outline: 0 !important;
        border-radius: 0;
        color: #7b8597;
        background: transparent !important;
        box-shadow: none !important;
        appearance: none;
        -webkit-appearance: none;
        opacity: 0;
        pointer-events: none;
        cursor: pointer;
        transition: color .15s ease, opacity .15s ease, transform .15s ease;
    }

    .jobs-clear:focus,
    .jobs-clear:focus-visible,
    .jobs-clear:active {
        border: 0 !important;
        outline: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .jobs-clear span {
        display: block;
        font-size: 25px;
        font-weight: 300;
        line-height: 1;
        transform: translateY(-1px);
    }

    .jobs-clear.is-visible { opacity: 1; pointer-events: auto; }
    .jobs-clear:hover { color: var(--career-red); transform: scale(1.08); }

    .jobs-filter {
        position: relative;
        min-height: 58px;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 12px;
        padding: 0 14px 0 18px;
        border-left: 1px solid #e4e7ee;
        color: #70798a;
    }

    .jobs-filter-label {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: #8a93a4;
        white-space: nowrap;
    }

    .jobs-filter-native {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    .jobs-filter-trigger {
        min-width: 0;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 0 10px 0 12px;
        border: 1px solid transparent;
        border-radius: 11px;
        color: #25304a;
        background: transparent;
        font: inherit;
        font-size: 14px;
        font-weight: 750;
        text-align: left;
        cursor: pointer;
        transition: background .18s ease, border-color .18s ease, color .18s ease;
    }

    .jobs-filter-trigger:hover,
    .jobs-filter.is-open .jobs-filter-trigger {
        border-color: #e6e9ef;
        background: #fff;
    }

    .jobs-filter-trigger > span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .jobs-filter-trigger i {
        flex: 0 0 20px;
        width: 20px;
        height: 20px;
        display: inline-grid;
        place-items: center;
        align-self: center;
        line-height: 1;
        color: #8c95a5;
        font-size: 13px;
        transform-origin: 50% 50%;
        transition: transform .2s ease, color .2s ease;
    }

    .jobs-filter-trigger i::before {
        display: block;
        line-height: 1;
        margin: 0;
    }

    .jobs-filter.is-open .jobs-filter-trigger i {
        color: var(--career-red);
        transform: rotate(180deg);
    }

    .jobs-filter-menu {
        position: absolute;
        top: calc(100% + 10px);
        right: 8px;
        z-index: 30;
        width: min(330px, calc(100vw - 32px));
        max-height: 300px;
        padding: 8px;
        overflow-y: auto;
        border: 1px solid #e4e7ee;
        border-radius: 16px;
        background: rgba(255,255,255,.98);
        box-shadow: 0 22px 50px rgba(29,46,89,.16);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(-6px) scale(.985);
        transform-origin: top right;
        transition: opacity .16s ease, transform .16s ease, visibility .16s ease;
        backdrop-filter: blur(18px);
    }

    .jobs-filter.is-open .jobs-filter-menu {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0) scale(1);
    }

    .jobs-filter-option {
        width: 100%;
        min-height: 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border: 0;
        border-radius: 10px;
        color: #39445b;
        background: transparent;
        font: inherit;
        font-size: 13px;
        font-weight: 650;
        text-align: left;
        cursor: pointer;
        transition: background .14s ease, color .14s ease;
    }

    .jobs-filter-option:hover { color: #171f32; background: #f6f7fa; }
    .jobs-filter-option i { color: transparent; font-size: 16px; }
    .jobs-filter-option.is-selected {
        color: #a91218;
        background: #fff1f2;
        font-weight: 800;
    }
    .jobs-filter-option.is-selected i { color: var(--career-red); }

    .jobs-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 24px 4px 14px;
        color: #7d8594;
        font-size: 13px;
        font-weight: 600;
    }

    .jobs-meta-row strong { color: #222d44; font-weight: 800; }
    .jobs-meta-row span:last-child { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }

    .jobs-list {
        overflow: hidden;
        border: 1px solid #e6e9f0;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 18px 54px rgba(29,46,89,.055);
    }

    .job-entry { margin: 0; }
    .job-entry + .job-entry { border-top: 1px solid #e9ebf1; }

    .job-row {
        width: 100%;
        min-height: 184px;
        display: grid;
        grid-template-columns: 116px minmax(0, 1.35fr) minmax(240px, .68fr) 136px;
        gap: 26px;
        align-items: center;
        padding: 24px 28px;
        border: 0;
        text-align: left;
        color: inherit;
        background: #fff;
        font-family: inherit;
        cursor: pointer;
        transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
    }

    .job-row:hover {
        position: relative;
        z-index: 2;
        background: linear-gradient(90deg, #fff8f8 0%, #fff 70%);
        box-shadow: inset 4px 0 0 var(--career-red);
    }

    .job-poster {
        width: 96px;
        height: 128px;
        overflow: hidden;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #f1f3f7;
        box-shadow: 0 10px 26px rgba(29,46,89,.12);
    }

    .job-poster img { width: 100%; height: 100%; object-fit: cover; }

    .job-poster-fallback {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        color: #fff;
        background: linear-gradient(150deg, #f24c53, #c8151c);
    }

    .job-poster-fallback i { font-size: 26px; }

    .job-main,
    .job-details,
    .job-action,
    .job-kicker,
    .job-title,
    .job-list-hint { display: block; }

    .job-kicker {
        color: var(--career-red);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .job-title {
        margin-top: 8px;
        color: #1d2638;
        font-size: 25px;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -.03em;
    }

    .job-list-hint {
        max-width: 660px;
        margin-top: 10px;
        color: #7a8393;
        font-size: 13px;
        line-height: 1.65;
    }

    .job-details {
        width: 100%;
        max-width: 230px;
        display: grid;
        gap: 10px;
        justify-self: start;
        align-self: center;
        color: #5f6879;
        font-size: 13px;
        font-weight: 700;
    }

    .job-detail-row {
        min-height: 24px;
        display: grid !important;
        grid-template-columns: 24px minmax(0, 1fr);
        align-items: center;
        column-gap: 10px;
        line-height: 1.35;
    }

    .job-details i {
        width: 24px;
        height: 24px;
        display: grid;
        place-items: center;
        margin: 0;
        color: #98a1b1;
        font-size: 14px;
        line-height: 1;
    }

    .job-detail-label {
        min-width: 0;
        display: block;
        align-self: center;
    }

    .job-action {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        color: #1f2a40;
        font-size: 13px;
        font-weight: 800;
    }

    .job-action i {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border: 1px solid #dfe3eb;
        border-radius: 50%;
        color: var(--career-red);
        background: #fff;
        transition: .2s ease;
    }

    .job-row:hover .job-action i {
        color: #fff;
        border-color: var(--career-red);
        background: var(--career-red);
        transform: translate(2px, -2px);
    }

    .jobs-empty-filter,
    .jobs-no-openings {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 16px;
        padding: 28px 30px;
        border: 1px solid #e6e9f0;
        border-radius: 18px;
        background: #fafbfc;
    }

    .jobs-empty-filter[hidden] { display: none; }

    .jobs-empty-icon {
        width: 50px;
        height: 50px;
        display: grid;
        place-items: center;
        flex: 0 0 50px;
        border-radius: 50%;
        color: var(--career-red);
        background: #fff0f1;
        font-size: 18px;
    }

    .jobs-empty-filter strong,
    .jobs-no-openings h3 {
        margin: 0;
        color: #25304a;
        font-size: 18px;
        font-weight: 800;
    }

    .jobs-empty-filter p,
    .jobs-no-openings p {
        margin: 5px 0 0;
        color: #7b8494;
        font-size: 14px;
    }

    .career-life {
        position: relative;
        overflow: hidden;
        padding: 122px 0;
        background: linear-gradient(120deg, #f8f9fc 0%, #fff9f5 100%);
    }

    .career-life::after {
        content: '';
        position: absolute;
        width: 320px;
        height: 320px;
        right: -160px;
        bottom: -160px;
        border: 52px solid rgba(237,28,36,.045);
        border-radius: 50%;
    }

    .career-life-grid {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(470px, .9fr);
        gap: 84px;
        align-items: center;
    }

    .career-life-copy h2 { max-width: 720px; }

    .career-life-lead,
    .career-process-lead {
        max-width: 660px;
        margin: 24px 0 0;
        color: var(--career-muted);
        font-size: 16px;
        line-height: 1.78;
    }

    .career-principles {
        margin-top: 38px;
        border-top: 1px solid #dfe3ea;
    }

    .career-principle {
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr);
        gap: 20px;
        padding: 24px 0;
        border-bottom: 1px solid #dfe3ea;
    }

    .principle-number {
        padding-top: 3px;
        color: var(--career-red);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .1em;
    }

    .career-principle h3 {
        margin: 0;
        color: #202b42;
        font-size: 19px;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .career-principle p {
        margin: 7px 0 0;
        color: #727b8b;
        font-size: 14px;
        line-height: 1.7;
    }

    .career-life-visual {
        position: relative;
        min-height: 530px;
        display: grid;
        place-items: center;
    }

    .career-life-visual img {
        position: relative;
        z-index: 2;
        width: min(100%, 620px);
        max-height: 530px;
        object-fit: contain;
    }

    .career-life-accent {
        position: absolute;
        width: 410px;
        height: 410px;
        border-radius: 46% 54% 62% 38% / 44% 44% 56% 56%;
        background: rgba(255,255,255,.76);
        box-shadow: 0 30px 70px rgba(29,46,89,.08);
        transform: rotate(-5deg);
    }

    .career-life-note {
        position: absolute;
        z-index: 4;
        left: 18px;
        bottom: 52px;
        padding: 14px 17px;
        border-left: 3px solid var(--career-red);
        border-radius: 0 12px 12px 0;
        background: rgba(255,255,255,.94);
        box-shadow: 0 16px 38px rgba(29,46,89,.1);
    }

    .career-life-note span,
    .career-life-note strong { display: block; }

    .career-life-note span {
        color: #8b93a3;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .career-life-note strong {
        margin-top: 4px;
        color: #24304a;
        font-size: 14px;
        font-weight: 800;
    }

    .career-process {
        padding: 118px 0;
        background: #fff;
    }

    .career-process-grid {
        display: grid;
        grid-template-columns: minmax(470px, .9fr) minmax(0, 1fr);
        gap: 92px;
        align-items: center;
    }

    .career-process-visual {
        min-height: 500px;
        display: grid;
        place-items: center;
    }

    .career-process-visual img {
        width: min(100%, 620px);
        max-height: 500px;
        object-fit: contain;
    }

    .career-process-copy h2 { max-width: 700px; }

    .career-steps {
        margin-top: 36px;
        border-top: 1px solid #e1e5ec;
    }

    .career-step {
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr);
        gap: 18px;
        padding: 22px 0;
        border-bottom: 1px solid #e1e5ec;
    }

    .career-step-number {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        color: #fff;
        background: var(--career-red);
        font-size: 13px;
        font-weight: 800;
        box-shadow: 0 8px 18px rgba(237,28,36,.18);
    }

    .career-step strong {
        display: block;
        color: #222d45;
        font-size: 18px;
        font-weight: 800;
    }

    .career-step p {
        margin: 7px 0 0;
        color: #737c8d;
        font-size: 14px;
        line-height: 1.65;
    }

    .career-final {
        position: relative;
        overflow: hidden;
        padding: 82px 0;
        color: #fff;
        background:
            radial-gradient(circle at 78% 20%, rgba(255,255,255,.14), transparent 22%),
            linear-gradient(118deg, #c9161d 0%, #ed1c24 55%, #b61017 100%);
    }

    .career-final::before {
        content: '';
        position: absolute;
        width: 310px;
        height: 310px;
        right: 9%;
        top: -180px;
        border: 54px solid rgba(255,255,255,.08);
        border-radius: 50%;
    }

    .career-final-inner {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 44px;
    }

    .career-final-kicker {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
        opacity: .76;
    }

    .career-final h2 {
        max-width: 760px;
        color: #fff;
        font-size: clamp(38px, 4vw, 54px);
    }

    .career-final p {
        max-width: 720px;
        margin: 15px 0 0;
        color: rgba(255,255,255,.8);
        font-size: 15px;
        line-height: 1.7;
    }

    .career-btn-white {
        flex: 0 0 auto;
        color: #171d2a;
        background: #fff;
        box-shadow: 0 16px 36px rgba(93,7,12,.2);
    }

    .career-btn-white:hover { color: var(--career-red); transform: translateY(-2px); }



    /* Premium public vacancy details modal */
    .career-job-modal .modal-dialog {
        width: min(1220px, calc(100vw - 24px));
        max-width: 1220px;
    }

    .career-job-modal .modal-backdrop,
    .career-job-modal + .modal-backdrop {
        backdrop-filter: blur(8px);
    }

    .career-job-modal-content {
        position: relative;
        overflow: hidden;
        max-height: calc(100vh - 32px);
        border: 1px solid rgba(29,46,89,.10);
        border-radius: 30px;
        background: #fff;
        box-shadow: 0 38px 110px rgba(24,35,58,.24);
    }

    .career-job-modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 8;
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        padding: 0;
        border: 1px solid #e2e7ef;
        border-radius: 14px;
        color: #26334e;
        background: rgba(255,255,255,.98);
        box-shadow: 0 10px 26px rgba(29,46,89,.10);
        transition: transform .18s ease, color .18s ease, border-color .18s ease, background .18s ease;
    }

    .career-job-modal-close span {
        width: 20px;
        height: 20px;
        display: grid;
        place-items: center;
        margin: 0;
        font-size: 28px;
        font-weight: 300;
        line-height: 18px;
        transform: translateY(-1px);
    }

    .career-job-modal-close:hover {
        color: #fff;
        border-color: var(--career-red);
        background: var(--career-red);
        transform: translateY(-1px);
    }

    .career-job-detail-grid {
        display: grid;
        grid-template-columns: minmax(390px, 1fr) minmax(0, 1.35fr);
        min-height: 0;
        height: min(820px, calc(100vh - 24px));
        overflow: hidden;
    }

    .career-job-detail-poster-wrap {
        position: relative;
        min-width: 0;
        display: grid;
        place-items: center;
        padding: 34px 24px 30px;
        overflow: hidden;
        background:
            radial-gradient(circle at 78% 12%, rgba(237,28,36,.11), transparent 25%),
            radial-gradient(circle at 8% 80%, rgba(29,46,89,.08), transparent 28%),
            linear-gradient(155deg, #fbfbfc 0%, #f5f7fa 52%, #eef2f7 100%);
        border-right: 1px solid #e7ebf1;
    }

    .career-job-detail-poster-wrap::before {
        content: 'RS8 CAREERS';
        position: absolute;
        left: 24px;
        top: 22px;
        color: #9aa4b5;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .16em;
    }

    .career-job-detail-poster-wrap::after {
        content: '';
        position: absolute;
        width: 130px;
        height: 130px;
        right: -72px;
        bottom: -60px;
        border: 20px solid rgba(237,28,36,.045);
        border-radius: 50%;
        pointer-events: none;
    }

    .career-job-detail-poster {
        position: relative;
        z-index: 1;
        width: min(100%, 420px);
        aspect-ratio: 3 / 4;
        max-height: 560px;
        overflow: hidden;
        display: grid;
        place-items: center;
        border: 1px solid rgba(29,46,89,.10);
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 28px 58px rgba(29,46,89,.16), 0 2px 0 rgba(255,255,255,.8) inset;
    }

    .career-job-detail-poster::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        border-radius: inherit;
        box-shadow: 0 0 0 1px rgba(255,255,255,.34) inset;
    }

    .career-job-detail-poster img {
        width: 100%;
        height: 100%;
        display: none;
        object-fit: cover;
    }

    .career-job-detail-poster.has-image img { display: block; }
    .career-job-detail-poster.has-image .career-job-detail-poster-fallback { display: none; }

    .career-job-detail-poster-fallback {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        align-content: center;
        gap: 12px;
        color: #fff;
        background: linear-gradient(155deg, #ef252d 0%, #c11219 48%, #151f38 100%);
    }

    .career-job-detail-poster-fallback i { font-size: 48px; }
    .career-job-detail-poster-fallback span {
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .career-job-detail-content {
        min-width: 0;
        min-height: 0;
        padding: 30px 36px 92px;
        overflow: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
        overscroll-behavior: contain;
        background:
            radial-gradient(circle at 100% 0, rgba(237,28,36,.035), transparent 22%),
            #fff;
    }

    .career-job-detail-content::-webkit-scrollbar {
        width: 0;
        height: 0;
        display: none;
    }

    .career-job-detail-eyebrow {
        max-width: calc(100% - 70px);
        padding-right: 0;
        color: var(--career-red);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .13em;
        line-height: 1.5;
        text-transform: uppercase;
    }

    .career-job-detail-content > h2 {
        max-width: 760px;
        margin: 10px 0 0;
        padding-right: 70px;
        color: #1b2742;
        font-size: clamp(34px, 3.5vw, 50px);
        font-weight: 800;
        line-height: .98;
        letter-spacing: -.048em;
    }

    .career-job-detail-intro {
        max-width: 690px;
        margin: 12px 0 0;
        color: #7a8597;
        font-size: 14px;
        line-height: 1.75;
    }

    .career-job-meta-grid {
        margin-top: 22px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .career-job-meta-item {
        min-width: 0;
        min-height: 72px;
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        align-items: center;
        column-gap: 12px;
        padding: 11px 13px;
        border: 1px solid #e8ecf2;
        border-radius: 17px;
        background: linear-gradient(180deg, #fff, #fafbfc);
        box-shadow: 0 8px 20px rgba(29,46,89,.035);
    }

    .career-job-meta-icon {
        width: 42px;
        height: 42px;
        flex: none;
        display: grid;
        place-items: center;
        align-self: center;
        justify-self: center;
        padding: 0;
        border: 1px solid #ffe0e2;
        border-radius: 13px;
        color: var(--career-red);
        background: #fff2f3;
        font-size: 16px;
        line-height: 1;
    }

    .career-job-meta-icon i {
        width: 18px;
        height: 18px;
        display: inline-grid;
        place-items: center;
        margin: 0;
        line-height: 1;
    }

    .career-job-meta-item > span:last-child {
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-self: stretch;
    }

    .career-job-meta-item small,
    .career-job-meta-item strong { display: block; }
    .career-job-meta-item small {
        margin: 0;
        color: #929bac;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.25;
    }
    .career-job-meta-item strong {
        margin-top: 4px;
        color: #26324c;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.32;
        overflow-wrap: anywhere;
    }

    .career-job-qualifications {
        margin-top: 20px;
        padding-top: 20px;
        padding-bottom: 8px;
        border-top: 1px solid #e8ecf2;
    }

    .career-job-section-heading {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr);
        align-items: center;
        column-gap: 14px;
    }

    .career-job-section-heading > span {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        padding: 0;
        border-radius: 14px;
        color: #fff;
        background: linear-gradient(145deg, #f52b33, #cc1118);
        box-shadow: 0 10px 20px rgba(237,28,36,.18);
        font-size: 17px;
        line-height: 1;
    }

    .career-job-section-heading > span i {
        width: 18px;
        height: 18px;
        display: grid;
        place-items: center;
        line-height: 1;
    }

    .career-job-section-heading h3 {
        margin: 0;
        color: #222d45;
        font-size: 21px;
        font-weight: 800;
        line-height: 1.2;
    }
    .career-job-section-heading p {
        margin: 4px 0 0;
        color: #8b95a6;
        font-size: 11px;
        line-height: 1.4;
    }

    #jobDetailQualifications {
        margin: 14px 0 0;
        padding: 0;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 10px;
        list-style: none;
    }

    #jobDetailQualifications li {
        position: relative;
        min-height: 40px;
        display: flex;
        align-items: center;
        padding: 8px 12px 8px 40px;
        border: 1px solid #e9edf2;
        border-radius: 14px;
        color: #536077;
        background: #fff;
        font-size: 12px;
        line-height: 1.42;
        box-shadow: 0 5px 14px rgba(29,46,89,.025);
    }

    #jobDetailQualifications li::before {
        content: '\2713';
        position: absolute;
        left: 11px;
        top: 50%;
        width: 20px;
        height: 20px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        color: #fff;
        background: #22a06b;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        transform: translateY(-50%);
        box-shadow: 0 5px 12px rgba(34,160,107,.18);
    }

    .career-job-detail-actions {
        position: absolute;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 3;
        margin: 0;
        padding: 12px 36px 14px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 11px;
        border-top: 1px solid #e7ebf1;
        background: rgba(255,255,255,.96);
        box-shadow: 0 -12px 26px rgba(29,46,89,.035);
        backdrop-filter: blur(12px);
    }

    .career-job-detail-actions form { margin: 0; }

    .career-job-secondary-btn,
    .career-job-apply-btn {
        min-height: 50px;
        padding: 0 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 800;
        line-height: 1;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
    }

    .career-job-secondary-btn {
        border: 1px solid #dde3eb;
        color: #536077;
        background: #fff;
    }

    .career-job-secondary-btn:hover {
        color: #202b43;
        border-color: #cfd6e0;
        background: #f7f8fa;
        transform: translateY(-1px);
    }

    .career-job-apply-btn {
        min-width: 158px;
        border: 1px solid var(--career-red);
        color: #fff;
        background: linear-gradient(135deg, #f52a32, #cc1118);
        box-shadow: 0 12px 26px rgba(237,28,36,.22);
    }

    .career-job-apply-btn i {
        width: 16px;
        height: 16px;
        display: grid;
        place-items: center;
        margin: 0;
        line-height: 1;
    }

    .career-job-apply-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 17px 30px rgba(237,28,36,.29);
    }

    /* Integrated Figma-inspired staff login - simplified */
    .staff-login-overlay {
        position: fixed;
        inset: 0;
        z-index: 1200;
        display: grid;
        place-items: center;
        padding: 28px;
        opacity: 0;
        visibility: hidden;
        transition: opacity .22s ease, visibility .22s ease;
    }

    .staff-login-overlay.is-open { opacity: 1; visibility: visible; }
    .staff-login-overlay[hidden] { display: none; }

    .staff-login-backdrop {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border: 0;
        background: rgba(24,35,58,.34);
        backdrop-filter: blur(10px);
        cursor: default;
    }

    .staff-login-dialog {
        position: relative;
        z-index: 2;
        width: min(920px, 100%);
        min-height: 520px;
        max-height: calc(100vh - 56px);
        display: grid;
        grid-template-columns: minmax(0, .9fr) minmax(360px, 1.1fr);
        overflow: hidden;
        border: 1px solid rgba(29,46,89,.10);
        border-radius: 28px;
        background: #fff;
        box-shadow: 0 36px 100px rgba(29,46,89,.24);
        transform: translateY(14px) scale(.988);
        transition: transform .24s ease;
    }

    .staff-login-overlay.is-open .staff-login-dialog { transform: translateY(0) scale(1); }

    .staff-login-close {
        position: absolute;
        z-index: 20;
        top: 16px;
        right: 16px;
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        padding: 0;
        border: 1px solid #dfe4eb;
        border-radius: 13px;
        color: #1f2b43;
        background: #fff;
        box-shadow: 0 8px 22px rgba(29,46,89,.10);
        transition: .18s ease;
    }

    .staff-login-close span {
        display: block;
        margin-top: -2px;
        font-size: 30px;
        font-weight: 400;
        line-height: 1;
    }

    .staff-login-close:hover { color: #fff; border-color: var(--career-red); background: var(--career-red); }

    .staff-login-showcase {
        position: relative;
        min-height: 520px;
        overflow: hidden;
        padding: 46px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background:
            radial-gradient(circle at 92% 8%, rgba(237,28,36,.10), transparent 28%),
            linear-gradient(145deg, #fff 0%, #fafbfc 48%, #f4f6f9 100%);
        border-right: 1px solid #e9ecf1;
    }

    .staff-login-showcase::after {
        content: '';
        position: absolute;
        width: 230px;
        height: 230px;
        right: -120px;
        bottom: -120px;
        border: 42px solid rgba(237,28,36,.045);
        border-radius: 50%;
        pointer-events: none;
    }

    .staff-login-logo {
        position: relative;
        z-index: 2;
        width: 176px;
        height: 56px;
        object-fit: contain;
        object-position: left center;
    }

    .staff-login-kicker {
        position: relative;
        z-index: 2;
        width: fit-content;
        margin-top: 42px;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: var(--career-red);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .15em;
        text-transform: uppercase;
    }

    .staff-login-kicker::before { content: ''; width: 25px; height: 2px; border-radius: 99px; background: currentColor; }

    .staff-login-showcase h2 {
        position: relative;
        z-index: 2;
        max-width: 430px;
        margin: 12px 0 0;
        color: #1d2e58;
        font-size: clamp(36px, 4vw, 50px);
        font-weight: 800;
        line-height: 1.02;
        letter-spacing: -.05em;
    }

    .staff-login-showcase > p {
        position: relative;
        z-index: 2;
        max-width: 430px;
        margin: 12px 0 0;
        color: #717d91;
        font-size: 13px;
        line-height: 1.75;
    }

    .staff-login-trust-row {
        position: relative;
        z-index: 2;
        margin-top: 28px;
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    .staff-login-trust-row span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 10px;
        border: 1px solid #e6e9ef;
        border-radius: 999px;
        color: #5d687c;
        background: rgba(255,255,255,.86);
        font-size: 10px;
        font-weight: 700;
    }
    .staff-login-trust-row i { color: var(--career-red); }

    .staff-login-form-panel {
        min-height: 520px;
        display: grid;
        place-items: center;
        padding: 54px 50px 46px;
        background: #fff;
    }

    .staff-login-form-wrap { width: 100%; max-width: 380px; }

    .staff-secure-chip {
        width: fit-content;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 10px;
        border: 1px solid #f4d4d6;
        border-radius: 999px;
        color: var(--career-red);
        background: #fff7f7;
        font-size: 10px;
        font-weight: 800;
    }

    .staff-login-form-wrap > h2 {
        margin: 18px 0 0;
        color: #1d2e58;
        font-size: 38px;
        font-weight: 800;
        line-height: 1.05;
        letter-spacing: -.045em;
    }

    .staff-login-form-wrap > p { margin: 9px 0 26px; color: #778296; font-size: 13px; line-height: 1.6; }

    .staff-login-alert {
        margin: -8px 0 16px;
        padding: 11px 13px;
        border: 1px solid #ffd4d7;
        border-radius: 12px;
        color: #9c151b;
        background: #fff3f4;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
    }

    .staff-login-field { display: block; margin-bottom: 14px; color: #2a354b; font-size: 11px; font-weight: 800; }
    .staff-login-field > label { display: block; margin: 0; color: inherit; font: inherit; }

    .staff-input-shell {
        position: relative;
        min-height: 54px;
        margin-top: 7px;
        display: flex;
        align-items: center;
        border: 1px solid #dfe4ea;
        border-radius: 14px;
        background: #fbfcfd;
        transition: .18s ease;
    }
    .staff-input-shell:focus-within { border-color: var(--career-red); background: #fff; box-shadow: 0 0 0 4px rgba(237,28,36,.07); }
    .staff-input-shell > i { width: 45px; display: grid; place-items: center; flex: 0 0 45px; color: #8f99a9; font-size: 15px; }
    .staff-input-shell input { min-width: 0; width: 100%; height: 52px; padding: 0 45px 0 0; border: 0; outline: 0; color: #1e293b; background: transparent; font: inherit; font-size: 13px; font-weight: 600; }
    .staff-input-shell input::placeholder { color: #a0a8b5; font-weight: 500; }

    .staff-password-toggle {
        position: absolute;
        right: 8px;
        top: 50%;
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        transform: translateY(-50%);
        border: 0;
        border-radius: 10px;
        color: #8993a3;
        background: transparent;
    }
    .staff-password-toggle:hover { color: var(--career-red); background: #fff0f1; }

    .staff-field-error { min-height: 15px; margin-top: 4px; display: block; color: #c71820; font-size: 10px; font-weight: 600; }

    .staff-login-submit {
        width: 100%;
        min-height: 54px;
        margin-top: 2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border: 0;
        border-radius: 14px;
        color: #fff;
        background: linear-gradient(135deg, #f1262e, #c91219);
        box-shadow: 0 14px 28px rgba(237,28,36,.20);
        font-size: 13px;
        font-weight: 800;
        transition: .18s ease;
    }
    .staff-login-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 18px 34px rgba(237,28,36,.27); }
    .staff-login-submit:disabled { opacity: .68; cursor: wait; }

    .staff-back-careers {
        margin-top: 18px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0;
        border: 0;
        color: #778296;
        background: transparent;
        font-size: 10px;
        font-weight: 800;
    }
    .staff-back-careers:hover { color: var(--career-red); }

    body.staff-login-open { overflow: hidden; }

    @media (max-width: 1199.98px) {
        .career-hero-grid { grid-template-columns: minmax(0, 1fr) minmax(420px, .92fr); gap: 34px; }
        .career-hero-copy h1 { font-size: clamp(54px, 5.4vw, 72px); }
        .career-hero-artwork { width: min(100%, 560px); }
        .career-hero-illustration { max-height: 490px; }
        .job-row { grid-template-columns: 104px minmax(0, 1fr) 220px 66px; gap: 20px; }
        .job-action span { display: none; }
        .career-life-grid,
        .career-process-grid { gap: 54px; }
    }




    @media (min-width: 901px) and (max-height: 780px) {
        .career-job-detail-grid { height: calc(100vh - 20px); }
        .career-job-detail-content { overflow-y: auto; scrollbar-width: none; -ms-overflow-style: none; }
        .career-job-detail-content::-webkit-scrollbar { display: none; }
    }
    @media (max-width: 900px) {
        .staff-login-overlay { padding: 18px; }
        .staff-login-dialog {
            width: min(560px, 100%);
            max-height: calc(100vh - 36px);
            grid-template-columns: 1fr;
            overflow-y: auto;
            border-radius: 24px;
        }
        .staff-login-showcase {
            min-height: auto;
            padding: 28px 28px 26px;
            border-right: 0;
            border-bottom: 1px solid #e9ecf1;
        }
        .staff-login-logo { width: 145px; height: 44px; }
        .staff-login-kicker { margin-top: 22px; }
        .staff-login-showcase h2 { max-width: 460px; font-size: 38px; }
        .staff-login-showcase > p { max-width: 470px; margin-top: 12px; }
        .staff-login-trust-row { margin-top: 18px; }
        .staff-login-form-panel { min-height: auto; padding: 34px 28px 38px; }
        .staff-login-close { top: 13px; right: 13px; }
    }

    @media (max-width: 575.98px) {
        .staff-login-overlay { padding: 0; }
        .staff-login-dialog {
            width: 100%;
            max-height: 100vh;
            min-height: 100vh;
            border: 0;
            border-radius: 0;
        }
        .staff-login-showcase { padding: 24px 20px 22px; }
        .staff-login-logo { width: 132px; height: 42px; }
        .staff-login-kicker { margin-top: 19px; font-size: 9px; }
        .staff-login-showcase h2 { font-size: 32px; }
        .staff-login-showcase > p { font-size: 12px; }
        .staff-login-trust-row span { padding: 7px 9px; font-size: 9px; }
        .staff-login-form-panel { padding: 30px 20px 38px; }
        .staff-login-form-wrap > h2 { font-size: 32px; }
        .staff-login-close { width: 40px; height: 40px; }
    }



    @media (max-width: 900px) {
        .career-job-modal .modal-dialog { width: min(760px, calc(100vw - 24px)); margin: 12px auto; }
        .career-job-detail-grid { grid-template-columns: 1fr; }
        .career-job-detail-poster-wrap {
            padding: 44px 20px 24px;
            border-right: 0;
            border-bottom: 1px solid #e7ebf1;
        }
        .career-job-detail-poster-wrap::before { left: 20px; top: 18px; }
        .career-job-detail-poster { width: min(100%, 340px); }
        .career-job-detail-content { padding: 32px 26px 112px; overflow-y: auto; overflow-x: hidden; scrollbar-width: none; -ms-overflow-style: none; }
        #jobDetailQualifications { grid-template-columns: 1fr; }
        .career-job-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .career-job-detail-actions {
            padding: 16px 26px 18px;
        }
    }

    @media (max-width: 575.98px) {
        .career-job-modal .modal-dialog { width: 100%; min-height: 100%; margin: 0; }
        .career-job-detail-grid { height: 100vh; overflow: hidden; }
        .career-job-modal-content { min-height: 100vh; max-height: 100vh; border: 0; border-radius: 0; }
        .career-job-modal-close { top: 12px; right: 12px; width: 40px; height: 40px; border-radius: 12px; }
        .career-job-detail-poster-wrap { padding: 42px 14px 18px; }
        .career-job-detail-poster-wrap::before { left: 14px; top: 16px; font-size: 9px; }
        .career-job-detail-poster { width: min(100%, 280px); border-radius: 18px; }
        .career-job-detail-content { padding: 26px 18px 150px; overflow-y: auto; overflow-x: hidden; scrollbar-width: none; -ms-overflow-style: none; }
        #jobDetailQualifications { grid-template-columns: 1fr; }
        .career-job-detail-content > h2 { padding-right: 0; font-size: 31px; line-height: 1.02; }
        .career-job-detail-eyebrow { max-width: calc(100% - 46px); }
        .career-job-detail-intro { font-size: 13px; }
        .career-job-meta-grid { grid-template-columns: 1fr; gap: 9px; }
        .career-job-meta-item { min-height: 72px; grid-template-columns: 40px minmax(0,1fr); padding: 11px 12px; }
        .career-job-meta-icon { width: 40px; height: 40px; border-radius: 12px; }
        .career-job-section-heading { grid-template-columns: 42px minmax(0,1fr); column-gap: 12px; }
        .career-job-section-heading > span { width: 42px; height: 42px; }
        #jobDetailQualifications li { padding-right: 12px; font-size: 12px; }
        .career-job-detail-actions {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            margin: 0;
            padding: 14px 18px 16px;
            align-items: stretch;
            flex-direction: column-reverse;
        }
        .career-job-secondary-btn, .career-job-apply-btn { width: 100%; min-height: 48px; }
        .career-job-detail-actions form { width: 100%; }
    }

    @media (max-width: 991.98px) {
        .career-shell { width: min(100% - 40px, 1280px); }
        .career-hero-grid { min-height: auto; grid-template-columns: 1fr; gap: 28px; padding-block: 66px 74px; }
        .career-hero-copy { text-align: center; }
        .career-hero-copy h1,
        .career-hero-lead { margin-inline: auto; }
        .career-eyebrow,
        .career-hero-actions,
        .career-hero-stats { justify-content: center; }
        .career-hero-visual { min-height: 490px; }
        .career-value-strip-inner { grid-template-columns: repeat(2, 1fr); }
        .career-value-item { min-height: 74px; }
        .career-value-item:nth-child(2) { border-right: 0; }
        .career-value-item:nth-child(-n+2) { border-bottom: 1px solid #eef0f4; }
        .section-heading { grid-template-columns: 1fr; gap: 20px; }
        .section-heading > p { max-width: 760px; }
        .job-row { grid-template-columns: 96px minmax(0, 1fr) 190px 48px; padding-inline: 22px; }
        .career-life-grid,
        .career-process-grid { grid-template-columns: 1fr; gap: 42px; }
        .career-life-copy { order: 1; }
        .career-life-visual { order: 2; min-height: 470px; }
        .career-process-visual { min-height: 450px; }
    }

    @media (max-width: 767.98px) {
        .career-shell { width: min(100% - 28px, 1280px); }
        .career-site { font-size: 15px; }
        .career-hero-grid { padding-block: 52px 58px; }
        .career-hero-copy h1 { font-size: clamp(42px, 11.8vw, 58px); }
        .career-hero-lead { margin-top: 22px; font-size: 16px; line-height: 1.72; }
        .career-hero-actions { display: grid; gap: 14px; }
        .career-btn-primary { width: 100%; }
        .career-hero-stats { gap: 12px; margin-top: 38px; }
        .career-stat { flex: 1; min-width: 0; padding-right: 12px; }
        .career-stat strong { font-size: 24px; }
        .career-stat span { font-size: 9px; }
        .career-hero-visual { min-height: 365px; padding: 8px 0 0; }
        .career-hero-artwork { width: min(100%, 430px); }
        .career-hero-illustration { max-height: 355px; }
        .career-value-strip-inner { grid-template-columns: 1fr; }
        .career-value-item { min-height: 64px; padding: 0 16px; border-right: 0 !important; border-bottom: 1px solid #eef0f4; }
        .career-value-item:last-child { border-bottom: 0; }
        .jobs-section,
        .career-life,
        .career-process { padding: 80px 0; }
        .section-heading h2,
        .career-life-copy h2,
        .career-process-copy h2 { font-size: 38px; }
        .jobs-control-panel { grid-template-columns: 1fr; margin-top: 34px; }
        .jobs-filter {
            border-top: 1px solid #e4e7ee;
            border-left: 0;
            grid-template-columns: 86px minmax(0, 1fr);
            overflow: visible;
        }
        .jobs-filter-menu {
            top: calc(100% + 8px);
            left: 8px;
            right: 8px;
            width: auto;
            max-height: min(280px, 46vh);
            transform-origin: top center;
            z-index: 120;
        }
        .jobs-meta-row span:last-child { display: none; }
        .jobs-list { border-radius: 16px; }
        .job-row {
            min-height: 148px;
            grid-template-columns: 74px minmax(0, 1fr) 42px;
            gap: 14px;
            padding: 18px 14px;
        }
        .job-poster { width: 66px; height: 88px; border-radius: 10px; }
        .job-title { font-size: 19px; }
        .job-list-hint { display: none; }
        .job-details {
            grid-column: 2 / 3;
            display: flex;
            flex-wrap: wrap;
            gap: 7px 12px;
            margin-top: -6px;
            font-size: 11px;
        }
        .job-details > span:nth-child(3) { display: none; }
        .job-action { grid-column: 3 / 4; grid-row: 1 / 3; align-self: center; }
        .job-action i { width: 36px; height: 36px; }
        .career-life-visual,
        .career-process-visual { min-height: 360px; }
        .career-life-visual img,
        .career-process-visual img { max-height: 350px; }
        .career-life-accent { width: 290px; height: 290px; }
        .career-life-note { left: 0; bottom: 18px; }
        .career-principle { grid-template-columns: 42px minmax(0, 1fr); gap: 12px; }
        .career-final { padding: 66px 0; }
        .career-final-inner { display: grid; gap: 28px; }
        .career-final h2 { font-size: 38px; }
        .career-btn-white { width: fit-content; }
    }

    @media (max-width: 767.98px) {
        /* Mobile alignment polish */
        .careers-premium .app-header {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
        }

        .careers-premium .app-header .container {
            min-height: 52px;
            padding-left: 16px;
            padding-right: 16px;
            gap: 10px !important;
        }

        .careers-premium .brand {
            min-width: 0;
            flex: 1 1 auto;
        }

        .careers-premium .brand-logo {
            width: 156px;
            height: 46px;
            max-width: calc(100vw - 86px);
            object-position: left center;
        }

        .careers-premium .staff-login-btn {
            width: 42px;
            height: 42px;
            min-width: 42px;
            min-height: 42px;
            flex: 0 0 42px;
            padding: 0;
            justify-content: center;
            border-radius: 12px;
        }

        .careers-premium .staff-login-btn i {
            display: grid;
            place-items: center;
            width: 18px;
            height: 18px;
            margin: 0;
            line-height: 1;
        }

        .career-hero-grid {
            gap: 22px;
            padding-block: 48px 44px;
        }

        .career-hero-copy {
            width: 100%;
        }

        .career-hero-copy .career-eyebrow {
            width: 100%;
            justify-content: center;
        }

        .career-hero-copy h1 {
            max-width: 330px;
            margin: 18px auto 0;
            line-height: 1.01;
        }

        .career-hero-lead {
            max-width: 330px;
            margin-inline: auto;
        }

        .career-hero-actions {
            width: min(100%, 280px);
            margin: 28px auto 0;
            justify-items: stretch;
        }

        .career-btn-primary,
        .career-hero-actions .career-link {
            width: 100%;
            justify-content: center;
        }

        .career-hero-actions .career-link {
            min-height: 42px;
        }

        .career-hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            width: min(100%, 330px);
            gap: 0;
            margin: 34px auto 0;
            align-items: stretch;
        }

        .career-stat {
            min-width: 0;
            padding: 0 8px;
            text-align: center;
        }

        .career-stat:not(:last-child)::after {
            top: 50%;
            right: 0;
            height: 42px;
            transform: translateY(-50%);
        }

        .career-stat strong {
            font-size: 24px;
        }

        .career-stat span {
            min-height: 25px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            margin-top: 6px;
            font-size: 8px;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .career-hero-visual {
            min-height: 0;
            margin-top: 4px;
            padding: 0;
        }

        .career-hero-artwork {
            width: min(100%, 360px);
        }

        .career-hero-illustration {
            width: 100%;
            max-height: none;
            margin-inline: auto;
        }

        .career-value-strip {
            padding: 8px 0;
        }

        .career-value-strip-inner {
            width: 100%;
            grid-template-columns: 1fr;
        }

        .career-value-item {
            min-height: 62px;
            display: grid;
            grid-template-columns: 38px 200px;
            justify-content: center;
            align-items: center;
            column-gap: 12px;
            padding: 12px 14px;
            text-align: left;
            border-right: 0 !important;
            border-bottom: 1px solid #eef0f4;
        }

        .career-value-item:last-child {
            border-bottom: 0;
        }

        .career-value-icon {
            width: 36px;
            height: 36px;
            flex-basis: 36px;
            margin: 0;
            border-radius: 11px;
        }

        .career-value-icon i {
            font-size: 16px;
            line-height: 1;
        }

        .career-value-item > span:last-child {
            min-height: 36px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            text-align: left;
            line-height: 1.3;
        }

        .section-heading,
        .section-heading-jobs {
            width: 100%;
        }

        .jobs-section,
        .career-life,
        .career-process {
            padding: 72px 0;
        }

        .jobs-control-panel {
            position: relative;
            z-index: 40;
            overflow: visible;
        }

        .jobs-filter {
            position: relative;
            z-index: 45;
        }

        .jobs-filter.is-open {
            z-index: 80;
        }

        .jobs-filter-menu {
            z-index: 100;
        }

        .job-row {
            align-items: center;
        }

        .job-main,
        .job-details {
            min-width: 0;
        }

        .job-action {
            justify-self: end;
            align-self: center;
        }

        .job-action i {
            display: grid;
            place-items: center;
            margin: 0;
        }

        .career-life-grid,
        .career-process-grid {
            gap: 34px;
        }

        .career-life-visual,
        .career-process-visual {
            min-height: 0;
            padding: 8px 0;
        }

        .career-life-visual img,
        .career-process-visual img {
            display: block;
            width: min(100%, 360px);
            max-height: none;
            margin-inline: auto;
        }

        .career-life-accent {
            width: 270px;
            height: 270px;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) rotate(-5deg);
        }

        .career-life-note {
            position: relative;
            left: auto;
            bottom: auto;
            width: fit-content;
            max-width: calc(100% - 24px);
            margin: -4px auto 0;
            text-align: left;
        }

        .career-principle {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 12px;
        }

        .principle-number {
            text-align: center;
        }

        .career-step {
            grid-template-columns: 42px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .career-step-number {
            margin: 0 auto;
        }

        .career-final-inner {
            align-items: start;
        }
    }

    @media (max-width: 480px) {
        .career-eyebrow { font-size: 10px; }
        .career-hero-copy h1 { max-width: 315px; font-size: clamp(38px, 11.3vw, 44px); }
        .career-hero-lead { max-width: 315px; }
        .career-hero-actions { width: min(100%, 250px); }
        .career-hero-stats { width: min(100%, 315px); }
        .career-stat { padding-inline: 6px; }
        .career-stat span { font-size: 7.5px; letter-spacing: .035em; }
        .career-hero-artwork { width: min(100%, 330px); }
        .career-value-item { grid-template-columns: 36px 190px; column-gap: 11px; }
        .job-row { grid-template-columns: 68px minmax(0, 1fr) 36px; gap: 10px; }
        .job-poster { width: 60px; height: 80px; }
        .job-kicker { font-size: 9px; }
        .job-title { font-size: 17px; }
        .job-details > span:nth-child(2) { display: none; }
        .career-final h2 { font-size: 34px; }
    }


    /* Final modal polish: cleaner poster pane, aligned login icons, responsive sizing */
    @media (min-width: 901px) {
        .career-job-modal .modal-dialog {
            width: min(1180px, calc(100vw - 40px));
            max-width: 1180px;
        }

        .career-job-detail-grid {
            grid-template-columns: minmax(340px, .82fr) minmax(0, 1.36fr);
            height: min(790px, calc(100vh - 34px));
        }

        .career-job-detail-poster-wrap {
            padding: 22px 18px;
            place-items: center;
            background:
                linear-gradient(180deg, rgba(255,255,255,.72), rgba(245,247,251,.90)),
                linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
            border-right: 1px solid #e5e9f0;
        }

        .career-job-detail-poster-wrap::before,
        .career-job-detail-poster-wrap::after {
            content: none !important;
            display: none !important;
        }

        .career-job-detail-poster {
            width: min(100%, 365px);
            max-height: calc(100vh - 78px);
            border-radius: 20px;
            border: 8px solid #fff;
            outline: 1px solid rgba(29,46,89,.08);
            box-shadow:
                0 28px 62px rgba(29,46,89,.18),
                0 8px 20px rgba(29,46,89,.08);
        }

        .career-job-detail-poster::after {
            box-shadow: 0 0 0 1px rgba(255,255,255,.62) inset;
        }

        .career-job-detail-content {
            position: relative;
            padding: 28px 34px 86px;
        }

        /* Keep the modal action/footer bar inside the right details pane only.
           This prevents it from covering the bottom of the full-height 3:4 poster. */
        .career-job-detail-actions {
            left: 0;
            right: 0;
            bottom: 0;
        }
    }

    /* Login form icon alignment */
    .staff-input-shell {
        position: relative;
        display: flex;
        align-items: center;
    }

    .staff-input-shell > i {
        position: absolute;
        left: 16px;
        top: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 18px;
        margin: 0;
        padding: 0;
        transform: translateY(-50%);
        color: #8f99a9;
        font-size: 15px;
        line-height: 1;
        pointer-events: none;
    }

    .staff-input-shell > i::before,
    .staff-password-toggle i::before,
    .staff-secure-chip i::before,
    .staff-login-trust-row i::before,
    .staff-login-submit i::before,
    .staff-back-careers i::before {
        display: block;
        margin: 0;
        line-height: 1;
    }

    .staff-input-shell input {
        height: 52px;
        padding: 0 48px 0 48px;
        line-height: 52px;
    }

    .staff-password-toggle {
        top: 50%;
        right: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transform: translateY(-50%);
        line-height: 1;
    }

    .staff-password-toggle i,
    .staff-secure-chip i,
    .staff-login-trust-row i,
    .staff-login-submit i,
    .staff-back-careers i {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        margin: 0;
        line-height: 1;
    }

    .staff-secure-chip,
    .staff-login-trust-row span,
    .staff-login-submit,
    .staff-back-careers {
        align-items: center;
    }

    @media (max-width: 900px) {
        .career-job-modal .modal-dialog {
            width: min(720px, calc(100vw - 24px));
            max-width: 720px;
            margin: 12px auto;
        }

        .career-job-modal-content {
            max-height: calc(100vh - 24px);
        }

        .career-job-detail-grid {
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: minmax(300px, 42vh) minmax(0, 1fr);
            height: calc(100vh - 24px);
        }

        .career-job-detail-poster-wrap {
            min-height: 0;
            padding: 18px;
            border-right: 0;
            border-bottom: 1px solid #e7ebf1;
            background: linear-gradient(180deg, #f8fafc, #f1f4f8);
        }

        .career-job-detail-poster-wrap::before,
        .career-job-detail-poster-wrap::after {
            content: none !important;
            display: none !important;
        }

        .career-job-detail-poster {
            width: auto;
            height: 100%;
            max-width: min(100%, 330px);
            max-height: 100%;
            border: 6px solid #fff;
            border-radius: 18px;
            outline: 1px solid rgba(29,46,89,.08);
            box-shadow: 0 18px 40px rgba(29,46,89,.15);
        }

        .career-job-detail-content {
            min-height: 0;
            padding: 26px 24px 112px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .staff-login-dialog {
            width: min(620px, 100%);
            max-height: calc(100vh - 32px);
            grid-template-columns: 1fr;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .staff-login-showcase {
            min-height: auto;
            padding: 26px 28px 24px;
        }

        .staff-login-form-panel {
            min-height: auto;
            padding: 32px 28px 36px;
        }

        .staff-login-form-wrap {
            max-width: 440px;
        }
    }

    @media (max-width: 575.98px) {
        .career-job-modal .modal-dialog {
            width: 100%;
            max-width: none;
            min-height: 100%;
            margin: 0;
        }

        .career-job-modal-content {
            min-height: 100dvh;
            max-height: 100dvh;
            border: 0;
            border-radius: 0;
        }

        .career-job-detail-grid {
            grid-template-rows: minmax(235px, 34dvh) minmax(0, 1fr);
            height: 100dvh;
        }

        .career-job-detail-poster-wrap {
            padding: 12px 54px 12px 12px;
        }

        .career-job-detail-poster {
            max-width: 220px;
            border-width: 5px;
            border-radius: 16px;
        }

        .career-job-detail-content {
            padding: 22px 16px 154px;
        }

        .career-job-modal-close {
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
        }

        .staff-login-overlay {
            padding: 0;
        }

        .staff-login-dialog {
            width: 100%;
            min-height: 100dvh;
            max-height: 100dvh;
            border: 0;
            border-radius: 0;
        }

        .staff-login-showcase {
            padding: 24px 20px 20px;
        }

        .staff-login-form-panel {
            padding: 26px 20px 34px;
        }

        .staff-input-shell {
            min-height: 52px;
        }

        .staff-input-shell > i {
            left: 15px;
        }

        .staff-input-shell input {
            height: 50px;
            padding-left: 46px;
            padding-right: 46px;
            line-height: 50px;
        }
    }

</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('jobSearch');
        const departmentFilter = document.getElementById('departmentFilter');
        const clearSearch = document.getElementById('clearSearch');
        const departmentFilterWrap = document.getElementById('departmentFilterWrap');
        const departmentFilterTrigger = document.getElementById('departmentFilterTrigger');
        const departmentFilterText = document.getElementById('departmentFilterText');
        const departmentFilterMenu = document.getElementById('departmentFilterMenu');
        const departmentOptions = Array.from(document.querySelectorAll('.jobs-filter-option'));
        const rows = Array.from(document.querySelectorAll('[data-job-row]'));
        const visibleRoleCount = document.getElementById('visibleRoleCount');
        const emptyState = document.getElementById('jobsEmptyFilter');

        if (!searchInput || !departmentFilter || rows.length === 0) {
            return;
        }

        const normalize = (value) => (value || '').toString().trim().toLowerCase();

        function filterJobs() {
            const query = normalize(searchInput.value);
            const department = normalize(departmentFilter.value);
            let visible = 0;

            rows.forEach(function (row) {
                const searchText = normalize(row.dataset.search);
                const rowDepartment = normalize(row.dataset.department);
                const matchesQuery = !query || searchText.includes(query);
                const matchesDepartment = !department || rowDepartment === department;
                const isVisible = matchesQuery && matchesDepartment;

                row.hidden = !isVisible;
                if (isVisible) visible += 1;
            });

            if (visibleRoleCount) {
                visibleRoleCount.textContent = visible;
            }

            if (emptyState) {
                emptyState.hidden = visible !== 0;
            }

            if (clearSearch) {
                clearSearch.classList.toggle('is-visible', searchInput.value.length > 0);
            }
        }

        searchInput.addEventListener('input', filterJobs);
        departmentFilter.addEventListener('change', filterJobs);

        if (departmentFilterTrigger && departmentFilterWrap) {
            departmentFilterTrigger.addEventListener('click', function (event) {
                event.stopPropagation();
                const willOpen = !departmentFilterWrap.classList.contains('is-open');
                departmentFilterWrap.classList.toggle('is-open', willOpen);
                departmentFilterTrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
        }

        departmentOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                const value = option.dataset.value || '';
                const label = option.querySelector('span') ? option.querySelector('span').textContent.trim() : 'All departments';

                departmentFilter.value = value;
                if (departmentFilterText) departmentFilterText.textContent = label;

                departmentOptions.forEach(function (item) {
                    const selected = item === option;
                    item.classList.toggle('is-selected', selected);
                    item.setAttribute('aria-selected', selected ? 'true' : 'false');
                });

                if (departmentFilterWrap) departmentFilterWrap.classList.remove('is-open');
                if (departmentFilterTrigger) departmentFilterTrigger.setAttribute('aria-expanded', 'false');
                filterJobs();
            });
        });

        document.addEventListener('click', function (event) {
            if (departmentFilterWrap && !departmentFilterWrap.contains(event.target)) {
                departmentFilterWrap.classList.remove('is-open');
                if (departmentFilterTrigger) departmentFilterTrigger.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && departmentFilterWrap && departmentFilterWrap.classList.contains('is-open')) {
                departmentFilterWrap.classList.remove('is-open');
                if (departmentFilterTrigger) {
                    departmentFilterTrigger.setAttribute('aria-expanded', 'false');
                    departmentFilterTrigger.focus();
                }
            }
        });

        if (clearSearch) {
            clearSearch.addEventListener('click', function () {
                searchInput.value = '';
                searchInput.focus();
                filterJobs();
            });
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('jobDetailsModal');
        const detailButtons = Array.from(document.querySelectorAll('[data-job-details]'));
        const jobDetails = {{ \Illuminate\Support\Js::from($jobDetailsPayload) }};

        if (!modalElement || detailButtons.length === 0 || typeof bootstrap === 'undefined') {
            return;
        }

        // Compatible with the Bootstrap version bundled in this project (5.0.0-beta1).
        // getOrCreateInstance() is not available in this older build, so use getInstance/new Modal.
        const modal = (typeof bootstrap.Modal.getInstance === 'function' && bootstrap.Modal.getInstance(modalElement))
            ? bootstrap.Modal.getInstance(modalElement)
            : new bootstrap.Modal(modalElement);
        const title = document.getElementById('jobDetailTitle');
        const department = document.getElementById('jobDetailDepartment');
        const employment = document.getElementById('jobDetailEmployment');
        const slots = document.getElementById('jobDetailSlots');
        const opening = document.getElementById('jobDetailOpening');
        const closing = document.getElementById('jobDetailClosing');
        const departmentMeta = document.getElementById('jobDetailDepartmentMeta');
        const salary = document.getElementById('jobDetailSalary');
        const qualifications = document.getElementById('jobDetailQualifications');
        const posterWrap = document.getElementById('jobDetailPosterWrap');
        const poster = document.getElementById('jobDetailPoster');
        const applyForm = document.getElementById('jobDetailsApplyForm');

        function formatSalary(minimum, maximum) {
            const money = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                maximumFractionDigits: 0
            });

            const min = minimum !== null && minimum !== '' ? Number(minimum) : null;
            const max = maximum !== null && maximum !== '' ? Number(maximum) : null;

            if (Number.isFinite(min) && Number.isFinite(max)) return money.format(min) + ' - ' + money.format(max);
            if (Number.isFinite(min)) return 'From ' + money.format(min);
            if (Number.isFinite(max)) return 'Up to ' + money.format(max);
            return 'Not specified';
        }

        function qualificationLines(value) {
            const raw = (value || '').toString().trim();
            if (!raw) return ['Qualifications will be provided by the recruitment team.'];

            const lines = raw
                .split(/\r?\n|;/)
                .map(function (line) {
                    return line.trim().replace(/^[-*•\u2022\s]+/, '').replace(/^\d+[.)]\s*/, '');
                })
                .filter(Boolean);

            return lines.length ? lines : [raw];
        }

        function openDetails(id) {
            const data = jobDetails[String(id)];
            if (!data) return;

            title.textContent = data.title || 'Job vacancy';
            department.textContent = [data.position, data.department].filter(Boolean).join(' | ');
            employment.textContent = data.employment_type || 'Not specified';
            slots.textContent = (data.slots || 0) + ' ' + (Number(data.slots) === 1 ? 'slot' : 'slots');
            opening.textContent = data.opening_date || 'Not specified';
            closing.textContent = data.closing_date || 'Open until filled';
            departmentMeta.textContent = data.department || 'RS8 Team';
            salary.textContent = formatSalary(data.salary_min, data.salary_max);

            qualifications.innerHTML = '';
            qualificationLines(data.qualifications).forEach(function (line) {
                const li = document.createElement('li');
                li.textContent = line;
                qualifications.appendChild(li);
            });

            if (data.poster_url) {
                poster.src = data.poster_url;
                poster.alt = (data.title || 'Job') + ' poster';
                posterWrap.classList.add('has-image');
            } else {
                poster.removeAttribute('src');
                poster.alt = '';
                posterWrap.classList.remove('has-image');
            }

            applyForm.action = data.apply_url;
            modal.show();
        }

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-job-details]');
            if (!button) return;

            event.preventDefault();
            openDetails(button.dataset.jobDetails);
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const overlay = document.getElementById('staffLoginOverlay');
        const triggers = Array.from(document.querySelectorAll('[data-staff-login-trigger]'));
        const closers = overlay ? Array.from(overlay.querySelectorAll('[data-staff-login-close]')) : [];
        const form = document.getElementById('staffLoginForm');
        const emailInput = document.getElementById('staffEmail');
        const passwordInput = document.getElementById('staffPassword');
        const passwordToggle = document.getElementById('staffPasswordToggle');
        const submitButton = document.getElementById('staffLoginSubmit');
        const alertBox = document.getElementById('staffLoginAlert');
        let lastFocusedElement = null;
        let hideTimer = null;

        if (!overlay) return;

        function clearErrors() {
            overlay.querySelectorAll('[data-error-for]').forEach(function (node) {
                node.textContent = '';
            });
            if (alertBox) {
                alertBox.hidden = true;
                alertBox.textContent = '';
            }
        }

        function setAlert(message) {
            if (!alertBox) return;
            alertBox.textContent = message;
            alertBox.hidden = false;
        }

        function cleanLoginQuery() {
            try {
                const url = new URL(window.location.href);
                if (url.searchParams.has('staff_login')) {
                    url.searchParams.delete('staff_login');
                    window.history.replaceState({}, '', url.pathname + (url.search ? url.search : '') + url.hash);
                }
            } catch (e) {}
        }

        function openStaffLogin() {
            if (hideTimer) window.clearTimeout(hideTimer);
            lastFocusedElement = document.activeElement;
            overlay.hidden = false;
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('staff-login-open');
            clearErrors();

            window.requestAnimationFrame(function () {
                overlay.classList.add('is-open');
            });

            window.setTimeout(function () {
                if (emailInput) emailInput.focus();
            }, 180);
        }

        function closeStaffLogin() {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('staff-login-open');
            cleanLoginQuery();

            hideTimer = window.setTimeout(function () {
                overlay.hidden = true;
            }, 230);

            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }
        }

        triggers.forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                openStaffLogin();
            });
        });

        closers.forEach(function (closer) {
            closer.addEventListener('click', closeStaffLogin);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
                closeStaffLogin();
            }
        });

        if (passwordToggle && passwordInput) {
            passwordToggle.addEventListener('click', function () {
                const shouldShow = passwordInput.type === 'password';
                passwordInput.type = shouldShow ? 'text' : 'password';
                const icon = passwordToggle.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bi-eye', !shouldShow);
                    icon.classList.toggle('bi-eye-slash', shouldShow);
                }
                passwordToggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            });
        }

        if (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                clearErrors();

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.querySelector('span').textContent = 'Signing in...';
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (e) {}

                    if (!response.ok) {
                        const errors = payload.errors || {};
                        Object.keys(errors).forEach(function (key) {
                            const errorNode = overlay.querySelector('[data-error-for="' + key + '"]');
                            if (errorNode) errorNode.textContent = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
                        });

                        setAlert(payload.message || (response.status === 419
                            ? 'Your session expired. Please refresh the page and try again.'
                            : 'Unable to sign in. Please check your account details.'));
                        return;
                    }

                    if (payload.redirect) {
                        window.location.assign(payload.redirect);
                        return;
                    }

                    setAlert('Login completed, but no destination was returned. Please try again.');
                } catch (error) {
                    setAlert('Unable to connect to the recruitment system. Please try again.');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.querySelector('span').textContent = 'Sign in to your account';
                    }
                }
            });
        }

        if (overlay.dataset.autoOpen === 'true') {
            openStaffLogin();
        }
    });
</script>
@endpush

{{-- Full-poster vacancy modal refinement --}}
@push('styles')
<style>
    /* Make the entire left pane the 3:4 vacancy poster on desktop. */
    @media (min-width: 901px) {
        .career-job-modal .modal-dialog {
            width: min(1180px, calc(100vw - 40px));
            max-width: 1180px;
        }

        .career-job-detail-grid {
            grid-template-columns: 46% 54%;
            height: min(724px, calc(100vh - 34px));
        }

        .career-job-detail-poster-wrap {
            padding: 0;
            display: block;
            background: #0e1420;
            border-right: 1px solid #e5e9f0;
        }

        .career-job-detail-poster-wrap::before,
        .career-job-detail-poster-wrap::after {
            content: none !important;
            display: none !important;
        }

        .career-job-detail-poster {
            width: 100%;
            height: 100%;
            max-width: none;
            max-height: none;
            aspect-ratio: auto;
            border: 0;
            border-radius: 0;
            outline: 0;
            background: #0e1420;
            box-shadow: none;
        }

        .career-job-detail-poster::after {
            content: none;
            display: none;
        }

        .career-job-detail-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
        }

        .career-job-detail-poster-fallback {
            width: 100%;
            height: 100%;
            border-radius: 0;
        }

        .career-job-detail-content {
            padding: 28px 34px 86px;
        }
    }

    /* Keep a clean, proportional poster presentation on tablets and phones. */
    @media (max-width: 900px) {
        .career-job-detail-poster-wrap {
            padding: 14px;
        }

        .career-job-detail-poster {
            width: auto;
            height: 100%;
            aspect-ratio: 3 / 4;
            max-width: 100%;
            max-height: 100%;
            border: 0;
            outline: 0;
            box-shadow: 0 14px 32px rgba(29,46,89,.14);
        }

        .career-job-detail-poster img {
            object-fit: contain;
        }
    }
</style>
@endpush

{{-- Mobile vacancy modal final responsive correction --}}
@push('styles')
<style>
    @media (max-width: 575.98px) {
        /* Let the whole modal page scroll naturally on phones instead of splitting
           poster/details into separate fixed-height panes. */
        .career-job-modal {
            padding: 0 !important;
        }

        .career-job-modal .modal-dialog {
            width: 100%;
            max-width: none;
            min-height: 100dvh;
            margin: 0;
        }

        .career-job-modal-content {
            min-height: 100dvh;
            max-height: none;
            overflow-x: hidden;
            overflow-y: auto;
            border: 0;
            border-radius: 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .career-job-modal-content::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .career-job-detail-grid {
            display: block;
            height: auto;
            min-height: 100dvh;
            overflow: visible;
        }

        .career-job-detail-poster-wrap {
            position: relative;
            width: 100%;
            min-height: 0;
            padding: 16px 16px 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 0;
            border-bottom: 1px solid #e7ebf1;
            background: linear-gradient(180deg, #f8fafc 0%, #f2f5f9 100%);
        }

        .career-job-detail-poster {
            width: min(58vw, 205px);
            height: auto;
            margin-inline: auto;
            transform: none;
            max-width: 205px;
            max-height: none;
            aspect-ratio: 3 / 4;
            overflow: hidden;
            border: 0;
            border-radius: 15px;
            outline: 0;
            background: #0e1420;
            box-shadow: 0 12px 28px rgba(29,46,89,.14);
        }

        .career-job-detail-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .career-job-modal-close {
            position: fixed;
            top: max(10px, env(safe-area-inset-top));
            right: 10px;
            z-index: 1065;
            width: 38px;
            height: 38px;
            border-radius: 12px;
        }

        .career-job-detail-content {
            min-height: 0;
            padding: 20px 14px 18px;
            overflow: visible;
        }

        .career-job-detail-eyebrow {
            max-width: 100%;
            font-size: 9px;
            line-height: 1.45;
        }

        .career-job-detail-content > h2 {
            margin-top: 8px;
            padding-right: 0;
            font-size: clamp(29px, 9vw, 34px);
            line-height: 1.02;
        }

        .career-job-detail-intro {
            margin-top: 10px;
            font-size: 12px;
            line-height: 1.55;
        }

        .career-job-meta-grid {
            margin-top: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .career-job-meta-item {
            min-height: 66px;
            grid-template-columns: 34px minmax(0, 1fr);
            column-gap: 8px;
            padding: 9px 9px;
            border-radius: 13px;
        }

        .career-job-meta-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            font-size: 13px;
        }

        .career-job-meta-item small {
            font-size: 8px;
        }

        .career-job-meta-item strong {
            margin-top: 3px;
            font-size: 10.5px;
            line-height: 1.28;
        }

        .career-job-qualifications {
            margin-top: 16px;
            padding-top: 16px;
            padding-bottom: 0;
        }

        .career-job-section-heading {
            grid-template-columns: 38px minmax(0, 1fr);
            column-gap: 10px;
        }

        .career-job-section-heading > span {
            width: 38px;
            height: 38px;
            border-radius: 11px;
        }

        .career-job-section-heading h3 {
            font-size: 19px;
        }

        .career-job-section-heading p {
            font-size: 9px;
        }

        #jobDetailQualifications {
            margin-top: 10px;
            grid-template-columns: 1fr;
            gap: 7px;
        }

        #jobDetailQualifications li {
            min-height: 42px;
            padding: 8px 10px 8px 38px;
            font-size: 11px;
            line-height: 1.38;
        }

        #jobDetailQualifications li::before {
            left: 10px;
            width: 19px;
            height: 19px;
            font-size: 10px;
        }

        /* Actions belong after the qualifications on mobile; no overlay/sticky cut-off. */
        .career-job-detail-actions {
            position: static;
            margin: 18px -14px -18px;
            padding: 12px 14px calc(14px + env(safe-area-inset-bottom));
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
            gap: 8px;
            border-top: 1px solid #e7ebf1;
            background: #fff;
            box-shadow: none;
            backdrop-filter: none;
        }

        .career-job-detail-actions form {
            width: 100%;
            margin: 0;
        }

        .career-job-secondary-btn,
        .career-job-apply-btn {
            width: 100%;
            min-height: 46px;
            padding: 0 10px;
            border-radius: 12px;
            font-size: 11px;
        }
    }

    @media (max-width: 360px) {
        .career-job-detail-poster {
            width: min(54vw, 185px);
        }

        .career-job-meta-grid {
            grid-template-columns: 1fr;
        }

        .career-job-detail-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
