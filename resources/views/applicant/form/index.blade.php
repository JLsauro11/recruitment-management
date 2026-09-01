@extends('layout.applicant')

@section('title', 'Apply for '.($vacancy->title ?? 'Position').' | RS8 Recruitment')

@section('content')
    @if($templates->isEmpty())
        <div class="alert alert-warning">No active employment form is available. Please contact HR.</div>
    @else
        @php
            $stepNumber = 1;
            $steps = collect();
            foreach ($templates as $template) {
                $groups = $template->fields->groupBy(fn($field) => $field->section ?: 'General Information');
                foreach ($groups as $section => $fields) {
                    $steps->push([
                        'key' => 'template_'.$template->id.'_'.\Illuminate\Support\Str::slug($section),
                        'title' => $section,
                        'subtitle' => $template->name,
                        'template' => $template,
                        'fields' => $fields,
                    ]);
                }
            }
            $steps->push(['key' => 'review', 'title' => 'Review & Submit', 'subtitle' => 'Confirm your application details']);
        @endphp

        <form method="POST" action="{{ route('careers.submit') }}" enctype="multipart/form-data" id="employmentForm" novalidate>
            @csrf

            <div class="employment-shell">
                <aside class="application-sidebar">
                    <div class="sidebar-progress-title">
                        <span class="sidebar-progress-kicker">APPLICATION FLOW</span>
                        <strong>Applying for {{ $vacancy->title }}</strong>
                        <small>Complete all required sections. Your application is saved only after submission.</small>
                    </div>

                    <div class="progress-summary">
                        <div class="d-flex justify-content-between align-items-center mb-2"><span>Application Progress</span><strong id="progressPercent">0%</strong></div>
                        <div class="progress-track"><div class="progress-fill" id="progressFill"></div></div>
                    </div>

                    <nav class="step-navigation" id="stepNavigation">
                        @foreach($steps as $index => $step)
                            <button type="button" class="step-nav-item {{ $index === 0 ? 'active' : '' }}" data-step="{{ $index }}">
                                <span class="step-nav-number">{{ $index + 1 }}</span>
                                <span><strong>{{ $step['title'] }}</strong><small>{{ $step['subtitle'] }}</small></span>
                            </button>
                        @endforeach
                    </nav>
                </aside>

                <main class="application-main">
                    <div class="application-hero">
                        <div>
                            <span class="hero-kicker">REDSPEED MOTOWORKZ OPC</span>
                            <h1>Application for Employment</h1>
                            <p>Complete each section carefully. Required fields are marked with an asterisk.</p>
                        </div>
                        <div class="secure-badge"><i class="bi bi-shield-check"></i><span>Secure Application</span></div>
                    </div>

                    <div class="application-job-context">
                        <div class="selected-job-icon"><i class="bi bi-briefcase-fill"></i></div>
                        <div class="selected-job-copy">
                            <span>YOU ARE APPLYING FOR</span>
                            <strong>{{ $vacancy->title }}</strong>
                            <small>
                                {{ $vacancy->position?->department?->name ?? 'RS8 Team' }}
                                <span class="context-divider">&bull;</span>
                                {{ $vacancy->employment_type }}
                            </small>
                            @error('form')
                                <div class="selected-job-error"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        <a href="{{ route('careers.index') }}#open-positions" class="change-job-link">
                            <i class="bi bi-arrow-left"></i>
                            Back to Job Openings
                        </a>
                    </div>

                    <div class="application-card">
                        @foreach($steps as $index => $step)
                            <section
                                    class="form-step {{ $index === 0 ? 'active' : '' }}"
                                    data-step="{{ $index }}"
                                    @if($step['key'] !== 'review')
                                    data-step-type="form_section"
                                    data-template-id="{{ $step['template']->id }}"
                                    data-section="{{ $step['title'] }}"
                                    @else
                                    data-step-type="review"
                                    @endif
                            >
                                <div class="step-header">
                                    <span class="step-eyebrow">STEP {{ $index + 1 }} OF {{ $steps->count() }}</span>
                                    <h2>{{ $step['title'] }}</h2>
                                    <p>{{ $step['subtitle'] }}</p>
                                </div>

                                @if($step['key'] === 'review')
                                    <div class="review-panel">
                                        <div class="review-icon"><i class="bi bi-check2-circle"></i></div>
                                        <h3>Review your application</h3>
                                        <p>All required-field errors will appear below. You may return to any section using the navigation before submitting.</p>
                                        <div class="review-checklist" id="reviewChecklist"></div>
                                    </div>
                                    <div class="acknowledgement-box">
                                        <div class="ack-icon"><i class="bi bi-file-earmark-check-fill"></i></div>
                                        <div><strong>Acknowledgement and Authorization</strong><p>By submitting this form, I certify that all information provided is true, complete, and correct. I authorize the company to verify information relevant to my application.</p></div>
                                    </div>
                                @else
                                    <div class="row g-4">
                                        @foreach($step['fields'] as $field)
                                            <div class="col-12 col-lg-{{ $field->width ?: 12 }}">
                                                <div class="premium-field {{ $field->field_type === 'file' ? 'file-field' : '' }}" data-required="{{ $field->is_required ? '1' : '0' }}" data-field-label="{{ $field->label }}" data-field-key="{{ $field->field_key }}">
                                                    <label class="form-label">{{ $field->label }} @if($field->is_required)<span class="text-danger">*</span>@endif</label>
                                                    @php
                                                        $name = 'answers['.$field->id.']';
                                                        $value = old('answers.'.$field->id, $existingAnswers[$field->id] ?? '');
                                                    @endphp

                                                    @if($field->field_type === 'textarea')
                                                        <textarea name="{{ $name }}" class="form-control" rows="5" placeholder="{{ $field->placeholder }}" @required($field->is_required)>{{ $value }}</textarea>
                                                    @elseif($field->field_type === 'select')
                                                        <select name="{{ $name }}" class="form-select" @required($field->is_required)>
                                                            <option value="">Select an option</option>
                                                            @foreach($field->options ?? [] as $option)<option value="{{ $option }}" @selected($value == $option)>{{ $option }}</option>@endforeach
                                                        </select>
                                                    @elseif($field->field_type === 'radio')
                                                        <div class="choice-grid">
                                                            @foreach($field->options ?? [] as $option)
                                                                <label class="choice-card"><input type="radio" name="{{ $name }}" value="{{ $option }}" @checked($value == $option) @required($field->is_required)><span class="choice-dot"></span><span>{{ $option }}</span></label>
                                                            @endforeach
                                                        </div>
                                                    @elseif($field->field_type === 'checkbox')
                                                        @php $checkedValues = is_array($value) ? $value : []; @endphp
                                                        <div class="choice-grid">
                                                            @foreach($field->options ?? [] as $option)
                                                                <label class="choice-card"><input type="checkbox" name="{{ $name }}[]" value="{{ $option }}" @checked(in_array($option, $checkedValues))><span class="choice-dot square"></span><span>{{ $option }}</span></label>
                                                            @endforeach
                                                        </div>
                                                    @elseif($field->field_type === 'file')
                                                        <label class="upload-zone">
                                                            <input type="file" name="{{ $name }}" class="file-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" @required($field->is_required && !$value)>
                                                            <span class="upload-icon"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                                                            <strong>Drag your file here or click to browse</strong>
                                                            <small>PDF, DOC, DOCX, JPG or PNG — maximum 5 MB</small>
                                                            <span class="selected-file-name">No file selected</span>
                                                        </label>
                                                    @else
                                                        <input type="{{ $field->field_type }}" name="{{ $name }}" class="form-control" value="{{ $value }}" placeholder="{{ $field->placeholder }}" @required($field->is_required)>
                                                    @endif
                                                    <div class="backend-error" data-error-for="answers.{{ $field->id }}">
                                                        @error('answers.'.$field->id){{ $message }}@enderror
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </section>
                        @endforeach

                        <div class="step-actions">
                            <button type="button" class="btn btn-light btn-lg" id="prevBtn"><i class="bi bi-arrow-left"></i> Previous</button>
                            <button type="button" class="btn btn-danger btn-lg" id="nextBtn">Continue <i class="bi bi-arrow-right"></i></button>
                            <button type="submit" class="btn btn-danger btn-lg d-none" id="submitBtn"><i class="bi bi-send-fill"></i> Submit Application</button>
                        </div>
                    </div>
                </main>
            </div>
        </form>
    @endif
@endsection

@push('styles')
<style>
    body {
        background: transparent !important;
    }

    .employment-shell {
        display: grid;
        grid-template-columns: 310px minmax(0, 1fr);
        min-height: calc(100dvh - 145px);
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 24px;
        background: rgba(255,255,255,.98);
        box-shadow: 0 30px 90px rgba(0,0,0,.42);
    }

    .application-sidebar {
        position: relative;
        padding: 26px 22px;
        color: #fff;
        background:
                radial-gradient(circle at 110% 75%, rgba(237,28,36,.42), transparent 35%),
                linear-gradient(180deg, rgba(5,8,13,.99), rgba(12,17,27,.98) 65%, rgba(54,8,12,.98));
    }

    .application-sidebar::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: repeating-linear-gradient(135deg, rgba(255,255,255,.018) 0, rgba(255,255,255,.018) 1px, transparent 1px, transparent 13px);
    }

    .application-sidebar > * {
        position: relative;
        z-index: 1;
    }

    .sidebar-progress-title {
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,.12);
    }

    .sidebar-progress-kicker {
        display: block;
        margin-bottom: 7px;
        color: #ff777d;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .16em;
    }

    .sidebar-progress-title strong,
    .sidebar-progress-title small {
        display: block;
    }

    .sidebar-progress-title strong {
        color: #fff;
        font-size: 14px;
        line-height: 1.35;
    }

    .sidebar-progress-title small {
        margin-top: 5px;
        color: rgba(255,255,255,.52);
        font-size: 10px;
        line-height: 1.5;
    }

    .progress-summary {
        margin: 24px 0;
        color: rgba(255,255,255,.75);
        font-size: 12px;
    }

    .progress-track {
        height: 7px;
        overflow: hidden;
        border-radius: 99px;
        background: rgba(255,255,255,.12);
    }

    .progress-fill {
        width: 0;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #ed1c24, #ff5b63);
        box-shadow: 0 0 16px rgba(237,28,36,.55);
        transition: width .3s ease;
    }

    .step-navigation {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .step-nav-item {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 11px;
        border: 1px solid transparent;
        border-radius: 12px;
        color: rgba(255,255,255,.63);
        background: transparent;
        text-align: left;
        transition: .18s ease;
    }

    .step-nav-item:hover {
        color: #fff;
        background: rgba(255,255,255,.06);
    }

    .step-nav-item.active {
        color: #fff;
        border-color: rgba(237,28,36,.45);
        background: linear-gradient(90deg, rgba(237,28,36,.26), rgba(237,28,36,.08));
        box-shadow: inset 3px 0 0 #ed1c24;
    }

    .step-nav-item.completed .step-nav-number {
        background: #16a34a;
    }

    .step-nav-number {
        width: 30px;
        height: 30px;
        min-width: 30px;
        display: grid;
        place-items: center;
        border-radius: 9px;
        background: rgba(255,255,255,.12);
        font-size: 11px;
        font-weight: 800;
    }

    .step-nav-item strong,
    .step-nav-item small {
        display: block;
    }

    .step-nav-item strong {
        font-size: 12px;
    }

    .step-nav-item small {
        margin-top: 2px;
        color: inherit;
        font-size: 9px;
    }

    .application-main {
        min-width: 0;
        background: #f8fafc;
    }

    .application-hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        overflow: hidden;
        padding: 34px 38px;
        color: #fff;
        background:
                linear-gradient(90deg, rgba(7,11,18,.96), rgba(18,25,39,.9) 58%, rgba(104,12,18,.86)),
                repeating-linear-gradient(120deg, rgba(255,255,255,.02) 0, rgba(255,255,255,.02) 1px, transparent 1px, transparent 12px);
    }

    .application-hero::after {
        content: "";
        position: absolute;
        width: 230px;
        height: 230px;
        right: -85px;
        bottom: -155px;
        border: 38px solid rgba(255,255,255,.06);
        border-radius: 50%;
    }

    .application-hero > * {
        position: relative;
        z-index: 1;
    }

    .application-hero h1 {
        margin: 5px 0 7px;
        color: #fff;
        font-size: 31px;
        font-weight: 900;
        letter-spacing: -.02em;
    }

    .application-hero p {
        margin: 0;
        color: rgba(255,255,255,.68);
    }

    .hero-kicker {
        color: #ff6067;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .15em;
    }

    .secure-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 13px;
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 999px;
        background: rgba(255,255,255,.07);
        font-size: 11px;
        white-space: nowrap;
    }

    .application-job-context {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 15px 38px;
        border-bottom: 1px solid #e6eaf0;
        background: #fff;
    }

    .selected-job-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        color: #fff;
        background: linear-gradient(135deg, #ed1c24, #b80e15);
        box-shadow: 0 9px 20px rgba(237,28,36,.2);
    }

    .selected-job-copy {
        min-width: 0;
        flex: 1;
    }

    .selected-job-copy > span,
    .selected-job-copy > strong,
    .selected-job-copy > small {
        display: block;
    }

    .selected-job-copy > span {
        color: #ed1c24;
        font-size: 8px;
        font-weight: 900;
        letter-spacing: .14em;
    }

    .selected-job-copy > strong {
        margin-top: 2px;
        color: #17233e;
        font-size: 15px;
        font-weight: 900;
    }

    .selected-job-copy > small {
        margin-top: 2px;
        color: #7b879b;
        font-size: 10px;
        font-weight: 700;
    }

    .context-divider {
        margin: 0 5px;
        color: #c1c8d3;
    }

    .selected-job-error {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin-top: 7px;
        color: #b91c1c;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.45;
    }

    .change-job-link {
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 12px;
        border: 1px solid #dfe4eb;
        border-radius: 10px;
        color: #536176;
        background: #f8fafc;
        font-size: 10px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        transition: .18s ease;
    }

    .change-job-link:hover {
        color: #ed1c24;
        border-color: rgba(237,28,36,.3);
        background: #fff5f5;
    }

    .application-card {
        min-height: 560px;
        padding: 34px 38px;
        background:
                linear-gradient(rgba(248,250,252,.97), rgba(248,250,252,.97)),
                repeating-linear-gradient(135deg, rgba(15,23,42,.025) 0, rgba(15,23,42,.025) 1px, transparent 1px, transparent 14px);
    }

    .form-step { display: none; }
    .form-step.active { display: block; }

    .step-header {
        padding-bottom: 20px;
        margin-bottom: 24px;
        border-bottom: 1px solid #e7ecf2;
    }

    .step-eyebrow {
        color: #ed1c24;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .13em;
    }

    .step-header h2 {
        margin: 6px 0 5px;
        color: #17233e;
        font-size: 24px;
        font-weight: 900;
    }

    .step-header p {
        margin: 0;
        color: #7b879b;
    }

    .premium-field { height: 100%; }

    .form-label {
        color: #34425a;
        font-size: 13px;
        font-weight: 800;
    }

    .form-control,
    .form-select {
        min-height: 49px;
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        background: #fff;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #ed1c24;
        box-shadow: 0 0 0 4px rgba(237,28,36,.08);
    }

    /* Laravel backend validation styling */
    .form-control.is-invalid,
    .form-select.is-invalid,
    select.is-invalid {
        border-color: #dc3545 !important;
        background-image: none !important;
        background-repeat: no-repeat !important;
        box-shadow: 0 0 0 4px rgba(220,53,69,.08) !important;
    }

    .vacancy-input-wrap {
        flex: 1 1 auto;
        min-width: 0;
    }

    .backend-error {
        display: block;
        width: 100%;
        min-height: 16px;
        margin-top: 7px;
        color: #dc3545;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.4;
    }

    textarea.form-control { min-height: 130px; }

    .vacancy-selection-card {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 24px;
        border: 1px solid #e3e8ef;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15,23,42,.07);
    }

    .vacancy-selection-icon {
        width: 58px;
        height: 58px;
        min-width: 58px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        color: #fff;
        background: linear-gradient(135deg,#ed1c24,#9f0d13);
        box-shadow: 0 12px 24px rgba(237,28,36,.22);
        font-size: 23px;
    }

    .choice-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
    }

    .choice-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 13px 14px;
        border: 1px solid #dfe5ec;
        border-radius: 12px;
        background: #fff;
        cursor: pointer;
        transition: .2s;
    }

    .choice-card:hover {
        border-color: #ed1c24;
        background: #fff8f8;
    }

    .choice-card input {
        position: absolute;
        opacity: 0;
    }

    .choice-dot {
        width: 18px;
        height: 18px;
        display: grid;
        place-items: center;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
    }

    .choice-dot.square { border-radius: 5px; }

    .choice-card input:checked + .choice-dot {
        border-color: #ed1c24;
        background: #ed1c24;
        box-shadow: inset 0 0 0 4px #fff;
    }

    .upload-zone {
        min-height: 180px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 24px;
        border: 2px dashed #d5dce5;
        border-radius: 16px;
        background: #fff;
        text-align: center;
        cursor: pointer;
    }

    .upload-zone:hover {
        border-color: #ed1c24;
        background: #fffafa;
    }

    .file-input { display: none; }

    .upload-icon {
        width: 50px;
        height: 50px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        color: #ed1c24;
        background: #fff1f2;
        font-size: 22px;
    }

    .upload-zone small { color: #8b96a9; }

    .selected-file-name {
        margin-top: 4px;
        color: #52617a;
        font-size: 11px;
    }

    .review-panel {
        padding: 32px;
        border: 1px solid #e4e9f0;
        border-radius: 18px;
        background: #fff;
        text-align: center;
    }

    .review-icon {
        width: 70px;
        height: 70px;
        display: grid;
        place-items: center;
        margin: 0 auto 14px;
        border-radius: 50%;
        color: #16a34a;
        background: #dcfce7;
        font-size: 31px;
    }

    .review-checklist {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
        gap: 10px;
        margin-top: 22px;
        text-align: left;
    }

    .review-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        border-radius: 12px;
        background: #f8fafc;
    }

    .review-item i { color: #16a34a; }

    .acknowledgement-box {
        display: flex;
        gap: 14px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #f3c7ca;
        border-radius: 15px;
        background: #fff7f7;
    }

    .ack-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        color: #ed1c24;
        background: #fee2e2;
    }

    .acknowledgement-box strong { color: #991b1b; }

    .acknowledgement-box p {
        margin: 5px 0 0;
        color: #59677c;
        font-size: 12px;
    }

    .step-actions {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding-top: 26px;
        margin-top: 30px;
        border-top: 1px solid #e7ecf2;
    }

    .step-actions .btn {
        min-width: 145px;
        border-radius: 12px;
    }

    #nextBtn,
    #submitBtn {
        border-color: #c41219;
        background: linear-gradient(135deg, #ed1c24, #b40e15);
        box-shadow: 0 10px 22px rgba(237,28,36,.2);
    }

    #nextBtn:hover,
    #submitBtn:hover {
        transform: translateY(-1px);
        box-shadow: 0 13px 28px rgba(237,28,36,.28);
    }

    @media (max-width: 991.98px) {
        .employment-shell { grid-template-columns: 1fr; }
        .application-sidebar { padding: 18px; }
        .step-navigation {
            flex-direction: row;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .step-nav-item { min-width: 180px; }
        .application-hero { padding: 27px 24px; }
        .application-card { padding: 26px 24px; }
    }

    @media (max-width: 575.98px) {
        .application-job-context {
            align-items: flex-start;
            flex-wrap: wrap;
            padding: 14px 18px;
        }

        .change-job-link {
            width: 100%;
            margin-left: 56px;
        }
        .employment-shell { border-radius: 0; }
        .application-hero {
            align-items: flex-start;
            flex-direction: column;
            padding: 24px 18px;
        }
        .application-hero h1 { font-size: 25px; }
        .application-card {
            min-height: calc(100dvh - 210px);
            padding: 22px 16px;
        }
        .vacancy-selection-card {
            align-items: flex-start;
            padding: 18px;
        }
        .step-actions {
            display: grid;
            grid-template-columns: 1fr;
        }
        .step-actions .btn { width: 100%; }
        .choice-grid { grid-template-columns: 1fr; }
        .secure-badge { display: none; }
    }

    .review-item { width: 100%; border: 1px solid transparent; text-align: left; cursor: pointer; }
    .review-item-valid { border-color: #dcfce7; }
    .review-item-error { align-items: flex-start; border-color: #fecaca; background: #fff7f7; }
    .review-item-error i { color: #dc2626; }
    .review-error-list { margin: 7px 0 0; padding-left: 17px; color: #b91c1c; font-size: 10px; line-height: 1.45; }
    .review-icon.has-errors { color: #dc2626; background: #fee2e2; }
    #submitBtn:disabled { cursor: not-allowed; opacity: .55; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('employmentForm');
        if (!form) return;

        const steps = [...document.querySelectorAll('.form-step')];
        const navItems = [...document.querySelectorAll('.step-nav-item')];
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        const progressFill = document.getElementById('progressFill');
        const progressPercent = document.getElementById('progressPercent');
        let current = 0;

        const serverErrors = @json($errors->toArray());

        function validationErrorKey(field) {
            const name = field?.getAttribute('name') || '';


            const answerMatch = name.match(/^answers\[(\d+)\]/);

            return answerMatch ? `answers.${answerMatch[1]}` : null;
        }

        function isDuplicateApplicationError(errorElement) {
            const message = errorElement?.textContent?.trim().toLowerCase() || '';
            return message.includes('already been submitted for this vacancy')
                || message.includes('already been submitted for the selected vacancy');
        }

        function isEmailAddressField(field) {
            return field?.closest('.premium-field')?.dataset.fieldKey === 'email_address';
        }

        function clearSingleFieldValidation(field) {
            if (!field) return;

            const name = field.getAttribute('name');
            const relatedFields = name
                ? [...form.querySelectorAll(`[name="${CSS.escape(name)}"]`)]
                : [field];

            relatedFields.forEach(item => item.classList.remove('is-invalid'));

            const wrapper = field.closest('.premium-field, .vacancy-input-wrap');
            const backendError = wrapper?.querySelector('.backend-error');

            if (backendError) {
                backendError.textContent = '';
                backendError.classList.remove('client-error');
            }

            const errorKey = validationErrorKey(field);

            if (errorKey) {
                delete serverErrors[errorKey];
            }
        }

        function clearFieldValidation(field) {
            if (!field) return;

            const wrapper = field.closest('.premium-field, .vacancy-input-wrap');
            const backendError = wrapper?.querySelector('.backend-error');
            clearSingleFieldValidation(field);

            if (current === steps.length - 1) {
                updateReview();
            }
        }

        function clearValidationState() {
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            form.querySelectorAll('.backend-error.client-error').forEach(error => {
                error.textContent = '';
            error.classList.remove('client-error');
        });

            form.querySelectorAll('.client-error:not(.backend-error)').forEach(error => error.remove());
        }

        function fieldLabel(field) {
            return field.closest('.premium-field')?.dataset.fieldLabel
            || field.closest('.vacancy-input-wrap')?.querySelector('.form-label')?.textContent.replace('*', '').trim()
            || 'Required field';
        }

        function markInvalid(field, message) {
            const wrapper = field.closest('.premium-field, .vacancy-input-wrap');
            const name = field.getAttribute('name');
            const related = name ? form.querySelectorAll(`[name="${CSS.escape(name)}"]`) : [field];
            related.forEach(el => el.classList.add('is-invalid'));

            if (wrapper) {
                let error = wrapper.querySelector('.backend-error');
                if (!error) {
                    error = document.createElement('div');
                    error.className = 'backend-error client-error';
                    wrapper.appendChild(error);
                } else {
                    error.classList.add('client-error');
                }
                error.textContent = message;
            }
        }

        function validateAllFields() {
            clearValidationState();
            const issues = [];

            steps.slice(0, -1).forEach((step, stepIndex) => {
                const stepIssues = [];

            step.querySelectorAll('.premium-field').forEach(wrapper => {
                const inputs = [...wrapper.querySelectorAll('input, select, textarea')];
            if (!inputs.length) return;

            const first = inputs[0];
            const required = wrapper.dataset.required === '1';
            const type = first.type;
            let message = '';

            if (required) {
                if (type === 'radio') {
                    if (!inputs.some(input => input.checked)) message = 'This field is required.';
                } else if (type === 'checkbox') {
                    if (!inputs.some(input => input.checked)) message = 'Please select at least one option.';
                } else if (type === 'file') {
                    if (!first.files.length && first.required) message = 'Please upload the required file.';
                } else if (!String(first.value || '').trim()) {
                    message = 'This field is required.';
                }
            }

            if (!message && first.value && !first.checkValidity()) {
                message = first.validationMessage || 'Please enter a valid value.';
            }

            if (message) {
                markInvalid(first, message);
                stepIssues.push({ field: first, label: fieldLabel(first), message });
                return;
            }

            const backendError = wrapper.querySelector('.backend-error:not(.client-error)');
            const backendMessage = backendError?.textContent.trim() || '';

            if (backendMessage) {
                first.classList.add('is-invalid');
                stepIssues.push({
                    field: first,
                    label: fieldLabel(first),
                    message: backendMessage
                });
            }
        });

            if (stepIssues.length) {
                issues.push({
                        stepIndex,
                        title: step.querySelector('h2')?.textContent || `Step ${stepIndex + 1}`,
                    issues: stepIssues
            });
            }
        });

            return issues;
        }

        function updateReview(issues = null) {
            const container = document.getElementById('reviewChecklist');
            if (!container) return;

            const validationIssues = issues ?? validateAllFields();
            const issueMap = new Map(validationIssues.map(group => [group.stepIndex, group]));

            container.innerHTML = steps.slice(0, -1).map((step, index) => {
                    const group = issueMap.get(index);
            const title = step.querySelector('h2')?.textContent || 'Section';

            if (!group) {
                return `
                        <button type="button" class="review-item review-item-valid" data-review-step="${index}">
                            <i class="bi bi-check-circle-fill"></i>
                            <div><strong>${title}</strong><small class="d-block text-muted">Complete</small></div>
                        </button>`;
            }

            const details = group.issues
                    .map(issue => `<li><strong>${issue.label}:</strong> ${issue.message}</li>`)
        .join('');

            return `
                    <button type="button" class="review-item review-item-error" data-review-step="${index}">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <div>
                            <strong>${title}</strong>
                            <small class="d-block text-danger">${group.issues.length} error${group.issues.length === 1 ? '' : 's'}</small>
                            <ul class="review-error-list">${details}</ul>
                        </div>
                    </button>`;
        }).join('');

            container.querySelectorAll('[data-review-step]').forEach(button => {
                button.addEventListener('click', () => showStep(Number(button.dataset.reviewStep)));
        });

            const reviewIcon = document.querySelector('.review-icon');
            if (reviewIcon) {
                reviewIcon.innerHTML = validationIssues.length
                    ? '<i class="bi bi-exclamation-triangle-fill"></i>'
                    : '<i class="bi bi-check2-circle"></i>';
                reviewIcon.classList.toggle('has-errors', validationIssues.length > 0);
            }

            submitBtn.disabled = validationIssues.length > 0;
            submitBtn.title = validationIssues.length ? 'Please correct all validation errors first.' : '';
        }

        function showStep(index) {
            current = Math.max(0, Math.min(index, steps.length - 1));
            steps.forEach((step, i) => step.classList.toggle('active', i === current));
            navItems.forEach((item, i) => {
                item.classList.toggle('active', i === current);
            item.classList.toggle('completed', i < current);
        });

            const percent = Math.round((current / (steps.length - 1)) * 100);
            progressFill.style.width = percent + '%';
            progressPercent.textContent = percent + '%';
            prevBtn.classList.toggle('invisible', current === 0);
            nextBtn.classList.toggle('d-none', current === steps.length - 1);
            submitBtn.classList.toggle('d-none', current !== steps.length - 1);

            if (current === steps.length - 1) updateReview();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        nextBtn.addEventListener('click', () => showStep(current + 1));
        prevBtn.addEventListener('click', () => showStep(current - 1));
        navItems.forEach((item, index) => item.addEventListener('click', () => showStep(index)));

        document.querySelectorAll('.file-input').forEach(input => {
            input.addEventListener('change', function () {
            const fileNameDisplay = this
                .closest('.upload-zone')
                ?.querySelector('.selected-file-name');

            if (fileNameDisplay) {
                fileNameDisplay.textContent = this.files[0]?.name || 'No file selected';
            }
        });
    });

        form.querySelectorAll('input, select, textarea').forEach(field => {
            const eventName = ['select-one', 'radio', 'checkbox', 'file'].includes(field.type)
                ? 'change'
                : 'input';

        field.addEventListener(eventName, function () {
            clearFieldValidation(this);
        });

        if (eventName !== 'change') {
            field.addEventListener('change', function () {
                clearFieldValidation(this);
            });
        }
    });

        form.addEventListener('submit', function (event) {
            const issues = validateAllFields();

            if (issues.length) {
                event.preventDefault();
                showStep(steps.length - 1);
                updateReview(issues);
                submitBtn.disabled = true;
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
        });

        @if($errors->any())
            current = steps.length - 1;
        @endif

        showStep(current);
    });
</script>
@endpush