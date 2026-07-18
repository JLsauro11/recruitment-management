<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login | RS8 Recruitment</title>

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">

    <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --rs8-red: #d71920;
            --rs8-red-dark: #a90f15;
            --rs8-black: #090909;
            --rs8-dark: #151515;
            --rs8-gray: #69707a;
            --rs8-border: #e5e7eb;
            --rs8-light: #f6f7f9;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            background:
                    radial-gradient(
                            circle at top right,
                            rgba(215, 25, 32, 0.13),
                            transparent 28%
                    ),
                    linear-gradient(135deg, #f8f9fb 0%, #eceff3 100%);
            color: var(--rs8-dark);
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px 20px;
        }

        .login-container {
            width: 100%;
            max-width: 1120px;
            min-height: 650px;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow:
                    0 30px 80px rgba(15, 23, 42, 0.14),
                    0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 55px;
            overflow: hidden;
            color: #ffffff;
            background:
                    linear-gradient(
                            145deg,
                            rgba(215, 25, 32, 0.97),
                            rgba(109, 4, 10, 0.98)
                    );
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            width: 390px;
            height: 390px;
            top: -180px;
            right: -160px;
            border-radius: 50%;
            border: 75px solid rgba(255, 255, 255, 0.08);
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            width: 310px;
            height: 310px;
            left: -170px;
            bottom: -150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
        }

        .brand-content,
        .brand-footer {
            position: relative;
            z-index: 2;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 30px;
        }

        .brand-logo-image {
            width: 250px;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .brand-panel h1 {
            max-width: 470px;
            margin-bottom: 22px;
            font-size: clamp(36px, 4vw, 58px);
            font-weight: 800;
            line-height: 1.06;
            letter-spacing: -2px;
        }

        .brand-panel p {
            max-width: 470px;
            margin: 0;
            font-size: 16px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.78);
        }

        .feature-list {
            display: grid;
            gap: 15px;
            margin-top: 38px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.88);
        }

        .feature-item i {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .brand-footer {
            margin-top: 45px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.58);
        }

        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 55px;
            background: #ffffff;
        }

        .login-form-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .mobile-logo {
            display: none;
        }

        .welcome-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            color: var(--rs8-red);
            background: rgba(215, 25, 32, 0.08);
        }

        .welcome-label::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--rs8-red);
            box-shadow: 0 0 0 5px rgba(215, 25, 32, 0.1);
        }

        .form-panel h2 {
            margin: 0 0 10px;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -1.3px;
            color: #151515;
        }

        .login-subtitle {
            margin-bottom: 36px;
            font-size: 15px;
            line-height: 1.7;
            color: var(--rs8-gray);
        }

        .form-label {
            margin-bottom: 9px;
            font-size: 13px;
            font-weight: 700;
            color: #30343b;
        }

        .input-group-premium {
            position: relative;
        }

        .input-icon {
            position: absolute;
            z-index: 3;
            top: 50%;
            left: 17px;
            transform: translateY(-50%);
            font-size: 18px;
            color: #9aa0a8;
            pointer-events: none;
        }

        .premium-input {
            width: 100%;
            height: 56px;
            padding: 0 52px 0 49px;
            border: 1px solid var(--rs8-border);
            border-radius: 14px;
            font-size: 14px;
            color: #20242a;
            background: #fafbfc;
            outline: none;
            transition: all 0.22s ease;
        }

        .premium-input::placeholder {
            color: #a6abb3;
        }

        .premium-input:hover {
            border-color: #cfd3d9;
            background: #ffffff;
        }

        .premium-input:focus {
            border-color: var(--rs8-red);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(215, 25, 32, 0.09);
        }

        .password-toggle {
            position: absolute;
            z-index: 4;
            top: 50%;
            right: 11px;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: 10px;
            color: #7e858e;
            background: transparent;
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--rs8-red);
            background: rgba(215, 25, 32, 0.08);
        }

        .field-error {
            display: block;
            min-height: 18px;
            margin-top: 6px;
            font-size: 12px;
        }

        .login-button {
            position: relative;
            width: 100%;
            height: 56px;
            margin-top: 9px;
            border: 0;
            border-radius: 14px;
            overflow: hidden;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.2px;
            color: #ffffff;
            background: linear-gradient(
                    135deg,
                    var(--rs8-red),
                    var(--rs8-red-dark)
            );
            box-shadow: 0 14px 30px rgba(215, 25, 32, 0.25);
            transition: all 0.22s ease;
        }

        .login-button::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                    90deg,
                    transparent,
                    rgba(255, 255, 255, 0.2),
                    transparent
            );
            transition: left 0.5s ease;
        }

        .login-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 18px 36px rgba(215, 25, 32, 0.33);
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:active:not(:disabled) {
            transform: translateY(0);
        }

        .login-button:disabled {
            cursor: not-allowed;
            opacity: 0.75;
        }

        .secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 24px;
            font-size: 12px;
            color: #969ca5;
        }

        .secure-note i {
            color: var(--rs8-red);
        }

        @media (max-width: 900px) {
            .login-container {
                max-width: 520px;
                min-height: auto;
                grid-template-columns: 1fr;
            }

            .brand-panel {
                display: none;
            }

            .form-panel {
                padding: 48px 35px;
            }

            .mobile-logo {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 42px;
            }

            .mobile-logo-icon {
                width: 46px;
                height: 46px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 13px;
                font-size: 21px;
                color: #ffffff;
                background: linear-gradient(
                        135deg,
                        var(--rs8-red),
                        var(--rs8-red-dark)
                );
            }

            .mobile-logo strong {
                display: block;
                font-size: 18px;
                line-height: 1.1;
            }

            .mobile-logo span {
                font-size: 10px;
                color: #9298a1;
                letter-spacing: 1.4px;
                text-transform: uppercase;
            }
        }

        @media (max-width: 576px) {
            .login-page {
                align-items: flex-start;
                padding: 0;
                background: #ffffff;
            }

            .login-container {
                min-height: 100vh;
                border-radius: 0;
                box-shadow: none;
            }

            .form-panel {
                align-items: flex-start;
                padding: 36px 24px;
            }

            .form-panel h2 {
                font-size: 31px;
            }

            .mobile-logo {
                margin-bottom: 55px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">
    <div class="login-container">

        <section class="brand-panel">
            <div class="brand-content">
                <div class="brand-logo">
                    <img
                            src="{{ asset('assets/images/logo/rs8-logo.png') }}"
                            alt="RS8 Logo"
                            class="brand-logo-image"
                    >
                </div>
                <h1>
                    Build the right team for the road ahead.
                </h1>

                <p>
                    Manage applicants, interviews, job vacancies, examinations,
                    and hiring decisions in one secure recruitment platform.
                </p>

                <div class="feature-list">
                    <div class="feature-item">
                        <i class="bi bi-check-lg"></i>
                        Centralized applicant management
                    </div>

                    <div class="feature-item">
                        <i class="bi bi-check-lg"></i>
                        Real-time recruitment monitoring
                    </div>

                    <div class="feature-item">
                        <i class="bi bi-check-lg"></i>
                        Secure role-based access
                    </div>
                </div>
            </div>

            <div class="brand-footer">
                © {{ date('Y') }} RS8 Moto Workz. All rights reserved.
            </div>
        </section>

        <section class="form-panel">
            <div class="login-form-wrapper">

                <div class="mobile-logo">
                    <div class="mobile-logo-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>

                    <div>
                        <strong>RS8 Recruitment</strong>
                        <span>Management System</span>
                    </div>
                </div>

                <div class="welcome-label">
                    Secure access
                </div>

                <h2>Welcome back</h2>

                <p class="login-subtitle">
                    Enter your account credentials to access the recruitment
                    management system.
                </p>

                <form
                        id="loginForm"
                        method="POST"
                        action="{{ route('login.attempt') }}"
                        novalidate
                >
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email address
                        </label>

                        <div class="input-group-premium">
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

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Password
                        </label>

                        <div class="input-group-premium">
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

                            <button
                                    type="button"
                                    id="togglePassword"
                                    class="password-toggle"
                                    aria-label="Show password"
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <small class="text-danger field-error password_error"></small>
                    </div>

                    <button
                            type="submit"
                            id="loginBtn"
                            class="login-button"
                    >
                        <span id="loginBtnText">
                            Sign in to your account
                        </span>
                    </button>
                </form>

                <div class="secure-note">
                    <i class="bi bi-shield-check"></i>
                    Your account is protected by secure authentication.
                </div>
            </div>
        </section>

    </div>
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

                passwordIcon
                    .removeClass('bi-eye')
                    .addClass('bi-eye-slash');

                $(this).attr('aria-label', 'Hide password');
            } else {
                passwordInput.attr('type', 'password');

                passwordIcon
                    .removeClass('bi-eye-slash')
                    .addClass('bi-eye');

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

                    didOpen: () => {
                    Swal.showLoading();
        }
        });

            $.ajax({
                type: 'POST',
                url: "{{ route('login.attempt') }}",
                dataType: 'json',
                data: $(this).serialize(),

                success: function (response) {
                    Swal.close();

                    showToast(
                        'success',
                        response.message || 'Login successful.'
                    );

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
                            text: xhr.responseJSON.message ||
                            'Please check the required fields.'
                        });

                    } else if (xhr.status === 401) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: xhr.responseJSON.message ||
                            'Invalid email or password.'
                        });

                    } else if (xhr.status === 403) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Account Unavailable',
                            text: xhr.responseJSON.message ||
                            'Your account is currently inactive.'
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
