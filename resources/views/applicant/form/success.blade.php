@extends('layout.applicant')

@section('title', 'Application Submitted | RS8 Recruitment')

@section('content')
<div class="success-shell">
    <div class="success-card text-center">
        <div class="success-icon"><i class="bi bi-check2-circle"></i></div>
        <span class="eyebrow">APPLICATION RECEIVED</span>
        <h1>Thank you, {{ session('applicant_name', 'Applicant') }}!</h1>
        <p>Your Application for Employment and Questionnaire have been submitted successfully.</p>
        <div class="reference-box">
            <small>APPLICATION REFERENCE NUMBER</small>
            <strong>{{ session('application_reference') }}</strong>
        </div>
        <p class="notice">Keep this reference number for future communication with our Recruitment Team.</p>
        <a href="{{ route('careers.apply') }}" class="btn btn-danger px-4">Submit Another Application</a>
    </div>
</div>
@endsection

@push('styles')
<style>
.success-shell{min-height:calc(100vh - 120px);display:grid;place-items:center;padding:30px 0}.success-card{width:min(680px,100%);padding:48px;border-radius:24px;background:#fff;box-shadow:0 22px 65px rgba(15,23,42,.1)}.success-icon{width:76px;height:76px;display:grid;place-items:center;margin:0 auto 20px;border-radius:50%;background:#dcfce7;color:#16a34a;font-size:36px}.eyebrow{font-size:11px;font-weight:800;letter-spacing:.16em;color:#ed1c24}.success-card h1{margin:10px 0;color:#17233e;font-weight:800}.success-card p{color:#64748b}.reference-box{margin:26px 0;padding:20px;border:1px dashed #ed1c24;border-radius:16px;background:#fff7f7}.reference-box small,.reference-box strong{display:block}.reference-box small{color:#8b96a9;font-weight:700;letter-spacing:.08em}.reference-box strong{margin-top:6px;color:#b70f16;font-size:26px;letter-spacing:.04em}.notice{font-size:13px}@media(max-width:576px){.success-shell{padding:0}.success-card{min-height:calc(100vh - 78px);padding:34px 20px;border-radius:0}}
</style>
@endpush
