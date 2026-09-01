<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login | RS8 Recruitment</title>

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/rs8_logo1.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">

    <script src="{{ asset('assets/vendors/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/sweetalert2/sweetalert2.all.min.js') }}"></script>

    <style>
        :root {
            --rs8-red: #ed1c24;
            --rs8-red-dark: #c4141b;
            --rs8-navy: #16233f;
            --rs8-ink: #151a24;
            --rs8-muted: #687386;
            --rs8-line: #e7eaf0;
            --rs8-soft: #f5f7fa;
            --rs8-red-soft: #fff1f2;
        }

        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Inter, Nunito, Arial, sans-serif;
            color: var(--rs8-ink);
            background:
                radial-gradient(circle at 88% 8%, rgba(237,28,36,.10), transparent 24%),
                radial-gradient(circle at 10% 92%, rgba(22,35,63,.055), transparent 28%),
                #f7f8fa;
        }

        .login-page {
            min-height: 100vh;
            padding: 28px;
            display: grid;
            place-items: center;
        }

        .login-shell {
            width: min(1180px, 100%);
            min-height: 710px;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(420px, .95fr);
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .07);
            border-radius: 30px;
            background: rgba(255,255,255,.92);
            box-shadow: 0 34px 90px rgba(22, 35, 63, .12);
            backdrop-filter: blur(18px);
        }

        .login-visual {
            position: relative;
            overflow: hidden;
            padding: 46px 50px 42px;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(circle at 84% 16%, rgba(237,28,36,.10), transparent 26%),
                linear-gradient(145deg, #fff 0%, #fafbfc 52%, #f4f6f9 100%);
            border-right: 1px solid var(--rs8-line);
        }

        .login-visual::before {
            content: '';
            position: absolute;
            width: 330px;
            height: 330px;
            right: -165px;
            top: -165px;
            border: 64px solid rgba(237,28,36,.055);
            border-radius: 50%;
        }

        .login-visual::after {
            content: '';
            position: absolute;
            width: 210px;
            height: 210px;
            left: -95px;
            bottom: -108px;
            border-radius: 50%;
            background: rgba(22,35,63,.035);
        }

        .brand-row,
        .visual-copy,
        .workflow-board,
        .visual-foot { position: relative; z-index: 2; }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .brand-row img {
            width: 190px;
            height: 58px;
            object-fit: contain;
            object-position: left center;
        }

        .brand-divider {
            width: 1px;
            height: 38px;
            background: #dfe3ea;
        }

        .brand-name strong,
        .brand-name span { display: block; }

        .brand-name strong {
            color: var(--rs8-navy);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .brand-name span {
            margin-top: 3px;
            color: var(--rs8-red);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .visual-copy {
            margin-top: 54px;
        }

        .visual-kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--rs8-red);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .visual-kicker::before {
            content: '';
            width: 28px;
            height: 2px;
            border-radius: 99px;
            background: currentColor;
        }

        .visual-copy h1 {
            max-width: 560px;
            margin: 18px 0 0;
            color: var(--rs8-navy);
            font-size: clamp(43px, 4.1vw, 62px);
            font-weight: 800;
            line-height: 1.02;
            letter-spacing: -.055em;
        }

        .visual-copy h1 span { color: var(--rs8-red); }

        .visual-copy p {
            max-width: 520px;
            margin: 20px 0 0;
            color: var(--rs8-muted);
            font-size: 15px;
            line-height: 1.75;
        }

        .workflow-board {
            margin-top: 38px;
            padding: 20px;
            border: 1px solid #e4e8ef;
            border-radius: 22px;
            background: rgba(255,255,255,.84);
            box-shadow: 0 18px 42px rgba(22,35,63,.075);
        }

        .workflow-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .workflow-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--rs8-navy);
            font-size: 13px;
            font-weight: 800;
        }

        .workflow-title i {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            color: #fff;
            background: var(--rs8-red);
            box-shadow: 0 9px 18px rgba(237,28,36,.18);
        }

        .workflow-live {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #41805f;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .workflow-live::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #38a169;
            box-shadow: 0 0 0 5px rgba(56,161,105,.10);
        }

        .workflow-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .workflow-card {
            min-height: 108px;
            padding: 15px;
            border: 1px solid #ebedf2;
            border-radius: 16px;
            background: #fff;
        }

        .workflow-card .icon {
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            color: var(--rs8-red);
            background: var(--rs8-red-soft);
        }

        .workflow-card strong {
            display: block;
            margin-top: 12px;
            color: #1c273d;
            font-size: 12px;
            font-weight: 800;
        }

        .workflow-card span {
            display: block;
            margin-top: 5px;
            color: #8a94a5;
            font-size: 10px;
            line-height: 1.45;
        }

        .visual-foot {
            margin-top: auto;
            padding-top: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: #8a93a3;
            font-size: 11px;
        }

        .visual-foot span:last-child {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .visual-foot i { color: var(--rs8-red); }

        .login-panel {
            position: relative;
            display: grid;
            place-items: center;
            padding: 54px 58px;
            background: #fff;
        }

        .login-panel::before {
            content: '';
            position: absolute;
            width: 180px;
            height: 180px;
            right: -80px;
            bottom: -86px;
            border: 34px solid rgba(237,28,36,.035);
            border-radius: 50%;
            pointer-events: none;
        }

        .login-form-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 410px;
        }

        .mobile-brand { display: none; }

        .secure-chip {
            width: fit-content;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border: 1px solid #f8d4d7;
            border-radius: 999px;
            color: var(--rs8-red);
            background: #fff7f7;
            font-size: 11px;
            font-weight: 800;
        }

        .secure-chip i { font-size: 12px; }

        .login-form-wrapper h2 {
            margin: 22px 0 0;
            color: var(--rs8-navy);
            font-size: 42px;
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -.045em;
        }

        .login-subtitle {
            margin: 13px 0 32px;
            color: var(--rs8-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .form-group { margin-bottom: 17px; }

        .form-label {
            margin-bottom: 8px;
            color: #29354b;
            font-size: 12px;
            font-weight: 800;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            color: #929cac;
            font-size: 17px;
            pointer-events: none;
        }

        .premium-input {
            width: 100%;
            height: 56px;
            padding: 0 48px 0 47px;
            border: 1px solid #dfe3e9;
            border-radius: 14px;
            outline: 0;
            color: #202a3c;
            background: #fbfcfd;
            font-size: 14px;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .premium-input::placeholder { color: #a0a8b4; }

        .premium-input:hover { border-color: #cdd3dc; background: #fff; }

        .premium-input:focus {
            border-color: var(--rs8-red);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(237,28,36,.075);
        }

        .password-toggle {
            position: absolute;
            z-index: 3;
            top: 50%;
            right: 10px;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: 10px;
            color: #8791a1;
            background: transparent;
            transition: .18s ease;
        }

        .password-toggle:hover { color: var(--rs8-red); background: var(--rs8-red-soft); }

        .field-error {
            display: block;
            min-height: 18px;
            margin-top: 5px;
            font-size: 11px;
        }

        .login-button {
            width: 100%;
            height: 56px;
            margin-top: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 0;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(135deg, #f1262e, #c91118);
            box-shadow: 0 15px 30px rgba(237,28,36,.20);
            font-size: 14px;
            font-weight: 800;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .login-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 19px 38px rgba(237,28,36,.27);
        }

        .login-button:disabled { opacity: .7; cursor: not-allowed; }

        .login-button i { font-size: 16px; }

        .login-meta {
            margin-top: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            color: #8a93a2;
            font-size: 11px;
        }

        .login-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .login-meta i { color: var(--rs8-red); }

        .career-link {
            color: #657083;
            font-weight: 700;
            text-decoration: none;
        }

        .career-link:hover { color: var(--rs8-red); }

        @media (max-width: 960px) {
            .login-page { padding: 22px; }
            .login-shell { max-width: 560px; min-height: auto; grid-template-columns: 1fr; }
            .login-visual { display: none; }
            .login-panel { min-height: 680px; padding: 52px 42px; }
            .mobile-brand {
                display: flex;
                align-items: center;
                gap: 14px;
                margin-bottom: 42px;
            }
            .mobile-brand img { width: 150px; height: 46px; object-fit: contain; object-position: left; }
            .mobile-brand-copy { padding-left: 13px; border-left: 1px solid #e2e5ea; }
            .mobile-brand-copy strong { display: block; color: var(--rs8-navy); font-size: 14px; font-weight: 800; }
            .mobile-brand-copy span { display: block; margin-top: 2px; color: var(--rs8-red); font-size: 9px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        }

        @media (max-width: 576px) {
            body { background: #fff; }
            .login-page { padding: 0; display: block; }
            .login-shell { min-height: 100vh; border: 0; border-radius: 0; box-shadow: none; }
            .login-panel { min-height: 100vh; align-items: start; padding: 30px 22px 42px; }
            .mobile-brand { margin-bottom: 58px; }
            .mobile-brand img { width: 135px; }
            .login-form-wrapper h2 { font-size: 36px; }
            .login-subtitle { margin-bottom: 28px; }
            .login-meta { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="login-page">
    <main class="login-shell">
        <section class="login-visual" aria-label="RS8 Recruitment overview">
            <div class="brand-row">
                <img src="{{ asset('assets/images/logo/rs8-logo.png') }}" alt="RS8">
                <span class="brand-divider" aria-hidden="true"></span>
                <span class="brand-name">
                    <strong>RS8 Recruitment</strong>
                    <span>Management System</span>
                </span>
            </div>

            <div class="visual-copy">
                <span class="visual-kicker">Recruitment workspace</span>
                <h1>Hire the right people. <span>Keep RS8 moving.</span></h1>
                <p>
                    Manage vacancies, applicants, interviews, and hiring decisions from one focused recruitment workspace built for the RS8 team.
                </p>
            </div>

            <div class="workflow-board" aria-hidden="true">
                <div class="workflow-top">
                    <span class="workflow-title"><i class="bi bi-grid-1x2-fill"></i> Recruitment workflow</span>
                    <span class="workflow-live">System ready</span>
                </div>
                <div class="workflow-grid">
                    <div class="workflow-card">
                        <span class="icon"><i class="bi bi-briefcase-fill"></i></span>
                        <strong>Job Vacancies</strong>
                        <span>Create and manage open recruitment positions.</span>
                    </div>
                    <div class="workflow-card">
                        <span class="icon"><i class="bi bi-people-fill"></i></span>
                        <strong>Applicants</strong>
                        <span>Review candidate information and progress.</span>
                    </div>
                    <div class="workflow-card">
                        <span class="icon"><i class="bi bi-check2-circle"></i></span>
                        <strong>Hiring Flow</strong>
                        <span>Move applicants through each recruitment stage.</span>
                    </div>
                </div>
            </div>

            <div class="visual-foot">
                <span>© {{ date('Y') }} RS8 Moto Workz</span>
                <span><i class="bi bi-shield-check"></i> Secure internal access</span>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-form-wrapper">
                <div class="mobile-brand">
                    <img src="{{ asset('assets/images/logo/rs8-logo.png') }}" alt="RS8">
                    <span class="mobile-brand-copy">
                        <strong>RS8 Recruitment</strong>
                        <span>Management System</span>
                    </span>
                </div>

                <span class="secure-chip"><i class="bi bi-shield-lock-fill"></i> Secure staff access</span>
                <h2>Welcome back.</h2>
                <p class="login-subtitle">Sign in with your authorized RS8 account to continue to the recruitment management system.</p>

                <form id="loginForm" method="POST" action="{{ route('login.attempt') }}" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="email" class="form-label">Email address</label>
                        <div class="input-wrap">
                            <i class="bi bi-envelope input-icon"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="premium-input"
                                placeholder="Enter your email address"
                                autocomplete="email"
                                required
                            >
                        </div>
                        <small class="text-danger field-error email_error"></small>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock input-icon"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="premium-input"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" id="togglePassword" class="password-toggle" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-danger field-error password_error"></small>
                    </div>

                    <button type="submit" id="loginBtn" class="login-button">
                        <span id="loginBtnText">Sign in to your account</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <div class="login-meta">
                    <span><i class="bi bi-lock-fill"></i> Protected by secure authentication</span>
                    <a href="{{ route('careers.index') }}" class="career-link">Back to Careers</a>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function clearErrors() {
            $('.email_error').text('');
            $('.password_error').text('');
        }

        function showToast(icon, message) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: message,
                showConfirmButton: false,
                showCloseButton: true,
                timer: 2500,
                timerProgressBar: true
            });
        }

        $('#togglePassword').click(function () {
            const passwordInput = $('#password');
            const passwordIcon = $(this).find('i');

            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                passwordIcon.removeClass('bi-eye').addClass('bi-eye-slash');
                $(this).attr('aria-label', 'Hide password');
            } else {
                passwordInput.attr('type', 'password');
                passwordIcon.removeClass('bi-eye-slash').addClass('bi-eye');
                $(this).attr('aria-label', 'Show password');
            }
        });

        $('#loginForm').submit(function (e) {
            e.preventDefault();
            clearErrors();

            const loginButton = $('#loginBtn');
            const loginButtonText = $('#loginBtnText');

            loginButton.prop('disabled', true);
            loginButtonText.text('Signing in...');

            Swal.fire({
                title: 'Signing in...',
                text: 'Please wait while we verify your account.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                type: 'POST',
                url: "{{ route('login.attempt') }}",
                dataType: 'json',
                data: $(this).serialize(),
                success: function (response) {
                    Swal.close();
                    showToast('success', response.message || 'Login successful.');
                    setTimeout(function () {
                        window.location.href = response.redirect;
                    }, 600);
                },
                error: function (xhr) {
                    Swal.close();

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors || {};
                        $.each(errors, function (key, value) {
                            $('.' + key + '_error').text(value[0]);
                        });
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: xhr.responseJSON.message || 'Please check the required fields.'
                        });
                    } else if (xhr.status === 401) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: xhr.responseJSON.message || 'Invalid email or password.'
                        });
                    } else if (xhr.status === 403) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Account Unavailable',
                            text: xhr.responseJSON.message || 'Your account is currently inactive.'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'System Error',
                            text: 'Something went wrong. Please try again.'
                        });
                    }
                },
                complete: function () {
                    loginButton.prop('disabled', false);
                    loginButtonText.text('Sign in to your account');
                }
            });
        });
    });
</script>
</body>
</html>
