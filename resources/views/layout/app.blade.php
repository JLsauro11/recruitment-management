<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | RS8 Recruitment</title>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/iconly/bold.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/perfect-scrollbar/perfect-scrollbar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons-latest.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/recruitment-admin.css') }}">
    <link rel="shortcut icon" href="{{ asset('assets//images/logo/rs8_logo1.png') }}" type="image/png">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    @stack('styles')
<style>
    .premium-header-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap}
    .premium-secondary-btn{
        min-height:42px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        padding:0 16px;
        border:1px solid #e2e7ee;
        border-radius:12px;
        color:#45536d;
        background:#fff;
        font-family:inherit;
        font-size:13px;
        font-weight:700;
        line-height:1;
        text-decoration:none;
        appearance:none;
        box-shadow:none;
        cursor:pointer;
        transition:.2s ease;
    }
    .premium-secondary-btn:hover{color:#ed1c24;border-color:#f0b7ba;background:#fff8f8;transform:translateY(-1px)}
    .premium-secondary-btn:focus{outline:0;box-shadow:0 0 0 3px rgba(237,28,36,.10)}
    .premium-secondary-btn i,.premium-secondary-btn i::before{display:block;line-height:1}
    .premium-secondary-btn:disabled{opacity:.65;cursor:not-allowed;transform:none}
    @media (max-width: 767.98px){.premium-page-header{align-items:flex-start!important;flex-direction:column}.premium-header-actions{width:100%;justify-content:stretch}.premium-header-actions .premium-primary-btn,.premium-header-actions .premium-secondary-btn{flex:1}}
</style>
</head>
<body>
<div id="app">
    @include('layout.sidebar')
    <div id="main">
        @include('layout.header')
        @include('layout.page-heading')
        @yield('content')
        @include('layout.footer')
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>window.hb_base_url = @json(url('/').'/');
    $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}});
    function showToast(icon, message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: message,
            showConfirmButton: false,
            showCloseButton: true,
            timer: 2500
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if(session('toast_success'))
            showToast('success', @json(session('toast_success')));
        @endif
        @if(session('toast_warning'))
            showToast('warning', @json(session('toast_warning')));
        @endif
        @if(session('toast_error'))
            showToast('error', @json(session('toast_error')));
        @endif
    });</script>
<script src="{{ asset('assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendors/apexcharts/apexcharts.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>
@stack('scripts')
</body>
</html>
