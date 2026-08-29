<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Application for Employment | RS8 Recruitment')</title>

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="shortcut icon" href="{{ asset('assets//images/logo/rs8_logo1.png') }}" type="image/png">


    <style>
        :root {
            --rs8-red: #ed1c24;
            --rs8-red-dark: #9f0d13;
            --rs8-black: #06080d;
            --rs8-navy: #111827;
            --rs8-slate: #1f2937;
        }

        * { box-sizing: border-box; }

        html { min-height: 100%; }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Nunito, Arial, sans-serif;
            color: #17233e;
            background:
                linear-gradient(rgba(3, 6, 12, .86), rgba(8, 12, 20, .92)),
                radial-gradient(circle at 12% 18%, rgba(237, 28, 36, .22), transparent 24%),
                radial-gradient(circle at 86% 26%, rgba(237, 28, 36, .18), transparent 22%),
                repeating-linear-gradient(120deg, rgba(255,255,255,.018) 0, rgba(255,255,255,.018) 1px, transparent 1px, transparent 14px),
                #080b11;
            background-attachment: fixed;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            z-index: -1;
            pointer-events: none;
        }

        body::before {
            inset: 0;
            background:
                linear-gradient(120deg, transparent 0 67%, rgba(237,28,36,.07) 67% 70%, transparent 70%),
                linear-gradient(120deg, transparent 0 74%, rgba(237,28,36,.11) 74% 77%, transparent 77%);
        }

        body::after {
            width: 520px;
            height: 520px;
            right: -180px;
            bottom: -220px;
            border: 70px solid rgba(237, 28, 36, .08);
            border-radius: 50%;
        }

        .app-header {
            position: sticky;
            top: 0;
            z-index: 30;
            border-bottom: 1px solid rgba(255,255,255,.08);
            background:
                radial-gradient(circle at 85% 0, rgba(237,28,36,.16), transparent 28%),
                linear-gradient(90deg, rgba(5,8,13,.98), rgba(13,18,28,.98));
            box-shadow: 0 12px 35px rgba(0,0,0,.25);
            backdrop-filter: blur(12px);
        }

        .app-header .container {
            max-width: 1420px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
            color: #fff !important;
        }

        .brand-logo {
            width: 205px;
            height: 58px;
            object-fit: contain;
            object-position: left center;
            filter: drop-shadow(0 6px 15px rgba(0,0,0,.25));
        }

        .brand-copy {
            padding-left: 16px;
            border-left: 1px solid rgba(255,255,255,.13);
        }

        .brand-copy strong,
        .brand-copy small {
            display: block;
        }

        .brand-copy strong {
            font-size: 17px;
            letter-spacing: .02em;
        }

        .brand-copy small {
            margin-top: 2px;
            color: #ff4b52;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .staff-login-btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0 15px;
            border: 1px solid rgba(237,28,36,.65);
            border-radius: 10px;
            color: #fff;
            background: rgba(255,255,255,.035);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s ease;
        }

        .staff-login-btn:hover {
            color: #fff;
            background: var(--rs8-red);
            box-shadow: 0 10px 24px rgba(237,28,36,.25);
            transform: translateY(-1px);
        }

        .public-main {
            max-width: 1420px;
            padding-top: 28px;
            padding-bottom: 38px;
        }

        @media (max-width: 767.98px) {
            .app-header .container {
                padding-left: 14px;
                padding-right: 14px;
            }

            .brand-logo {
                width: 138px;
                height: 44px;
            }

            .brand-copy { display: none; }

            .staff-login-btn span { display: none; }

            .staff-login-btn {
                width: 40px;
                justify-content: center;
                padding: 0;
            }

            .public-main {
                max-width: none;
                padding: 0;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
<header class="app-header py-3">
    <div class="container d-flex justify-content-between align-items-center gap-3">
        <a href="{{ route('careers.apply') }}" class="brand text-decoration-none">
            <img
                src="{{ asset('assets/images/logo/rs8-public-logo.png') }}"
                alt="RS8 Taiwan Speed Factory"
                class="brand-logo"
            >

            <div class="brand-copy">
                <strong>RS8 Recruitment</strong>
                <small>Application for Employment</small>
            </div>
        </a>

        <a href="{{ route('login') }}" class="staff-login-btn">
            <i class="bi bi-person"></i>
            <span>Staff Login</span>
        </a>
    </div>
</header>

<main class="container public-main">
    @yield('content')
</main>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
