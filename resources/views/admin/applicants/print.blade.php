<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $application->reference_no }} - Applicant Forms</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef1f5;
            color: #17233e;
            font-family: Arial, Helvetica, sans-serif;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: #fff;
            background: #151d34;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .18);
        }

        .toolbar strong {
            font-size: 14px;
        }

        .toolbar small {
            display: block;
            margin-top: 2px;
            color: rgba(255, 255, 255, .68);
        }

        .toolbar button {
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            color: #fff;
            background: #ed1c24;
            font-weight: 700;
            cursor: pointer;
        }

        .document {
            width: 210mm;
            margin: 14px auto;
        }

        .form-page {
            position: relative;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 14px;
            background: #fff;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .12);
            page-break-after: always;
            break-after: page;
            overflow: hidden;
        }

        .form-page:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        .page-header{
            width:100%;
            height:16mm;      /* pwede 15-17mm */
            overflow:hidden;
            background:#fff;
        }

        .page-header img{
            display:block;
            width:100%;
            height:100%;
            object-fit:fill;   /* <-- ito ang importante */
        }

        .page-content {
            padding: 4mm 10mm 18mm;
        }

        .reference-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2mm;
            color: #65718a;
            font-size: 8px;
            text-align: right;
        }

        .reference-row strong {
            display: block;
            margin-top: 1px;
            color: #17233e;
            font-size: 10px;
        }

        .title {
            margin: 0 0 2px;
            text-align: center;
            font-size: 16px;
            text-transform: uppercase;
        }

        .description {
            margin: 0 0 8px;
            text-align: center;
            color: #65718a;
            font-size: 8px;
        }

        .summary {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr;
            gap: 6px;
            margin-bottom: 8px;
            padding: 6px 8px;
            border: 1px solid #dce2eb;
            border-radius: 6px;
            background: #f8fafc;
        }

        .summary-label {
            display: block;
            color: #7b879b;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 2px;
            font-size: 8px;
            font-weight: 700;
        }

        .answers {
            column-count: 3;
            column-gap: 7px;
        }

        .section {
            column-span: all;
            margin: 5px 0 4px;
            padding: 4px 6px;
            border-left: 3px solid #ed1c24;
            background: #f3f5f8;
            font-size: 7px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .4px;
            break-after: avoid;
        }

        .answer {
            position: relative;
            display: inline-block;
            width: 100%;
            min-height: 32px;
            margin: 0 0 3px;
            padding: 3px 5px;
            border: 1px solid #e2e7ee;
            border-radius: 4px;
            break-inside: avoid;
        }

        .label {
            position: relative;
            z-index: 2;
            color: #69758a;
            font-size: 6.5px;
            font-weight: 700;
            line-height: 1.05;
        }

        .value {
            margin-top: 3px;
            color: #111827;
            font-size: 7.5px;
            line-height: 1.1;
            text-align: center;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* Dash na eksaktong nasa center ng buong box */
        .answer-empty::after {
            content: "\2014";
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 7.5px;
            font-weight: 600;
            line-height: 1;
            pointer-events: none;
        }

        .file-value {
            font-style: italic;
            color: #334155;
        }

        .page-footer{
            position:absolute;
            left:0;
            right:0;
            bottom:0;
            height:15mm;     /* pwede 14-16mm */
            overflow:hidden;
            background:#fff;
        }

        .page-footer img{
            width:100%;
            height:100%;
            object-fit:fill;
        }

        .page-number {
            position: absolute;
            right: 4mm;
            bottom: 1.2mm;
            z-index: 2;
            padding: 1px 4px;
            border-radius: 6px;
            color: #17233e;
            background: rgba(255, 255, 255, .88);
            font-size: 6px;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none !important;
            }

            .document {
                width: auto;
                margin: 0;
            }

            .form-page {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <div>
        <strong>{{ $application->applicant?->full_name ?? 'Applicant' }} — {{ $application->reference_no }}</strong>
        <small>{{ $submissions->count() }} page(s), based on the submitted form templates</small>
    </div>
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="document">
    @foreach($submissions as $pageIndex => $submission)
        @php
            $template = $submission->template;
            $answers = $submission->answers
                ->sortBy(fn($answer) => [$answer->field?->sort_order ?? PHP_INT_MAX, $answer->id])
                ->values();
            $currentSection = null;
        @endphp
        <section class="form-page">
            <header class="page-header">
                <img src="{{ asset('assets/images/print/redspeed-header.png') }}" alt="Redspeed Motoworkz OPC Header">
            </header>

            <div class="page-content">
                <div class="reference-row">
                    <div>REFERENCE NO.<strong>{{ $application->reference_no }}</strong></div>
                </div>

                <h1 class="title">{{ $template?->name ?? 'Employment Form' }}</h1>
                <p class="description">{{ $template?->description }}</p>

                <div class="summary">
                    <div><span class="summary-label">Applicant</span><div class="summary-value">{{ $application->applicant?->full_name ?? 'N/A' }}</div></div>
                    <div><span class="summary-label">Position</span><div class="summary-value">{{ $application->vacancy?->position?->name ?? $application->vacancy?->title ?? 'N/A' }}</div></div>
                    <div><span class="summary-label">Submitted</span><div class="summary-value">{{ optional($submission->submitted_at)->format('M d, Y g:i A') }}</div></div>
                </div>

                <div class="answers">
                    @foreach($answers as $answer)
                        @php
                            $field = $answer->field;
                        @endphp

                        @if(strtolower(trim($field?->label ?? '')) === 'resume')
                            @continue
                        @endif

                        @php
                            $section = $field?->section ?: 'General Information';
                            $value = $answer->value;

                            if (is_string($value) && str_starts_with(trim($value), '[')) {
                                $decoded = json_decode($value, true);

                                if (is_array($decoded)) {
                                    $value = implode(', ', $decoded);
                                }
                            }
                        @endphp

                        @if($section !== $currentSection)
                            @php($currentSection = $section)
                            <div class="section">{{ $section }}</div>
                        @endif

                        <div class="answer {{ filled($value) ? '' : 'answer-empty' }}">
                            <div class="label">{{ $field?->label ?? 'Field' }}</div>

                            @if($field?->field_type === 'file' && $value)
                                <div class="value file-value">
                                    Attached file: {{ basename($value) }}
                                </div>
                            @elseif(filled($value))
                                <div class="value">{{ $value }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <footer class="page-footer">
                <img src="{{ asset('assets/images/print/redspeed-footer.png') }}" alt="Redspeed Motoworkz OPC Footer">
                <span class="page-number">Page {{ $pageIndex + 1 }} of {{ $submissions->count() }}</span>
            </footer>
        </section>
    @endforeach
</div>
</body>
</html>
