<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $application->reference_no }} - Applicant Forms</title>
    <style>
        @page{size:A4 portrait;margin:0}*{box-sizing:border-box}body{margin:0;background:#eef1f5;color:#17233e;font-family:Arial,Helvetica,sans-serif}.toolbar{position:sticky;top:0;z-index:20;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 18px;color:#fff;background:#151d34;box-shadow:0 5px 18px rgba(15,23,42,.18)}.toolbar strong{font-size:14px}.toolbar small{display:block;margin-top:2px;color:rgba(255,255,255,.68)}.toolbar button{border:0;border-radius:8px;padding:10px 16px;color:#fff;background:#ed1c24;font-weight:700;cursor:pointer}.document{width:210mm;margin:14px auto}.form-page{position:relative;width:210mm;height:297mm;min-height:297mm;max-height:297mm;margin:0 auto 14px;background:#fff;box-shadow:0 8px 25px rgba(15,23,42,.12);page-break-after:always;break-after:page;overflow:hidden}.form-page:last-child{page-break-after:auto;break-after:auto}.page-header{width:100%;height:16mm;overflow:hidden;background:#fff}.page-header img,.page-footer img{display:block;width:100%;height:100%;object-fit:fill}.page-content{height:266mm;padding:4mm 10mm 17mm;overflow:hidden}.reference-row{display:flex;justify-content:flex-end;margin-bottom:2mm;color:#65718a;font-size:8px;text-align:right}.reference-row strong{display:block;margin-top:1px;color:#17233e;font-size:10px}.title{margin:0 0 2px;text-align:center;font-size:16px;text-transform:uppercase}.description{margin:0 0 8px;text-align:center;color:#65718a;font-size:8px}.continuation{font-weight:700;color:#7a8496}.summary{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:6px;margin-bottom:8px;padding:6px 8px;border:1px solid #dce2eb;border-radius:6px;background:#f8fafc}.summary-label{display:block;color:#7b879b;font-size:6.5px;font-weight:700;text-transform:uppercase}.summary-value{margin-top:2px;font-size:8px;font-weight:700}.form-section{margin-top:5px;break-inside:avoid}.section{margin:0 0 4px;padding:4px 6px;border-left:3px solid #ed1c24;background:#f3f5f8;font-size:7px;font-weight:800;text-transform:uppercase;letter-spacing:.4px}.answer-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:4px 6px;align-items:stretch}.answer{position:relative;min-height:34px;padding:4px 6px;border:1px solid #e2e7ee;border-radius:5px;break-inside:avoid;background:#fff}.label{color:#69758a;font-size:6.5px;font-weight:700;line-height:1.25}.value{margin-top:3px;color:#111827;font-size:7.5px;line-height:1.25;text-align:center;white-space:pre-wrap;overflow-wrap:anywhere}.answer-empty::after{content:"\2014";position:absolute;inset:15px 0 0;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:7.5px;font-weight:600;pointer-events:none}.record-stack{display:grid;gap:6px}.record-card{border:1px solid #dfe5ec;border-radius:6px;overflow:hidden;break-inside:avoid;background:#fff}.record-title{display:flex;justify-content:space-between;align-items:center;padding:4px 7px;background:#fafbfc;border-bottom:1px solid #e8ecf1;font-size:7px;font-weight:800;color:#27324a;text-transform:uppercase}.record-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0}.record-grid .answer{border:0;border-right:1px solid #edf0f4;border-bottom:1px solid #edf0f4;border-radius:0;min-height:32px}.record-grid .answer.wide{grid-column:1/-1;min-height:42px}.record-grid .answer:nth-child(4n){border-right:0}.duration-badge{font-size:6.5px;color:#7a8496;font-weight:700}.questionnaire-grid{display:grid;grid-template-columns:1fr;gap:7px}.questionnaire-grid .answer{min-height:70px;padding:7px 8px}.questionnaire-grid .label{color:#253047;font-size:7.3px;line-height:1.3;padding-bottom:5px;margin-bottom:5px;border-bottom:1px solid #edf0f4}.questionnaire-grid .value{margin-top:0;font-size:7.5px;line-height:1.42;text-align:left}.page-footer{position:absolute;left:0;right:0;bottom:0;height:15mm;overflow:hidden;background:#fff}.page-number{position:absolute;right:4mm;bottom:1.2mm;z-index:2;padding:1px 4px;border-radius:6px;color:#17233e;background:rgba(255,255,255,.88);font-size:6px}.empty-note{padding:8px;border:1px dashed #dce2ea;border-radius:6px;color:#8a94a6;text-align:center;font-size:7px}@media print{body{background:#fff}.toolbar{display:none!important}.document{width:auto;margin:0}.form-page{margin:0;box-shadow:none}}
    </style>
</head>
<body>
@php
    $printPages = collect();
    foreach ($submissions as $submission) {
        $template = $submission->template;
        $answers = $submission->answers->sortBy(fn($answer) => [$answer->field?->sort_order ?? PHP_INT_MAX, $answer->id])->values();
        $isQuestionnaire = str_contains(strtolower(($template?->name ?? '').' '.($template?->type ?? '')), 'questionnaire');
        $printable = $answers->reject(function($answer){
            $field=$answer->field; $label=strtolower(trim($field?->label??'')); $key=strtolower(trim($field?->field_key??''));
            return $field?->field_type==='file' && (str_contains($label,'resume')||str_contains($label,'cv')||str_contains($key,'resume')||str_contains($key,'cv'));
        })->values();
        if ($isQuestionnaire) {
            $groups=$printable->groupBy(fn($a)=>$a->field?->section ?: 'Questionnaire');
            foreach ($groups as $sectionName=>$group) {
                foreach ($group->chunk(3) as $chunkIndex=>$chunk) {
                    $printPages->push(['submission'=>$submission,'template'=>$template,'questionnaire'=>true,'sections'=>collect([$sectionName=>$chunk]),'continuation'=>$chunkIndex>0]);
                }
            }
        } else {
            $groups=$printable->groupBy(fn($a)=>$a->field?->section ?: 'General Information');
            $primaryNames=['Job Application Data','Personal Information','Educational Background'];
            $primary=$groups->filter(fn($v,$k)=>in_array($k,$primaryNames,true));
            $secondary=$groups->reject(fn($v,$k)=>in_array($k,$primaryNames,true));
            if($primary->isNotEmpty()) $printPages->push(['submission'=>$submission,'template'=>$template,'questionnaire'=>false,'sections'=>$primary,'continuation'=>false]);
            if($secondary->isNotEmpty()) $printPages->push(['submission'=>$submission,'template'=>$template,'questionnaire'=>false,'sections'=>$secondary,'continuation'=>$primary->isNotEmpty()]);
            if($primary->isEmpty() && $secondary->isEmpty()) $printPages->push(['submission'=>$submission,'template'=>$template,'questionnaire'=>false,'sections'=>collect(),'continuation'=>false]);
        }
    }
@endphp
<div class="toolbar"><div><strong>{{ $application->applicant?->full_name ?? 'Applicant' }} — {{ $application->reference_no }}</strong><small>{{ $printPages->count() }} printable page(s), organized by submitted form section</small></div><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
<div class="document">
@foreach($printPages as $pageIndex=>$page)
    @php $submission=$page['submission']; $template=$page['template']; @endphp
    <section class="form-page">
        <header class="page-header"><img src="{{ asset('assets/images/print/redspeed-header.png') }}" alt="Redspeed Motoworkz OPC Header"></header>
        <div class="page-content">
            <div class="reference-row"><div>REFERENCE NO.<strong>{{ $application->reference_no }}</strong></div></div>
            <h1 class="title">{{ $template?->name ?? 'Employment Form' }} @if($page['continuation'])<span class="continuation">— Continued</span>@endif</h1>
            <p class="description">{{ $template?->description }}</p>
            <div class="summary">
                <div><span class="summary-label">Applicant</span><div class="summary-value">{{ $application->applicant?->full_name ?? 'N/A' }}</div></div>
                <div><span class="summary-label">Position Applied For</span><div class="summary-value">{{ $application->vacancy?->title ?? $application->vacancy?->position?->name ?? 'N/A' }}</div></div>
                <div><span class="summary-label">Submitted</span><div class="summary-value">{{ optional($submission->submitted_at)->format('M d, Y g:i A') }}</div></div>
            </div>

            @forelse($page['sections'] as $sectionName=>$sectionAnswers)
                <div class="form-section">
                    <div class="section">{{ $sectionName }}</div>
                    @if($page['questionnaire'])
                        <div class="questionnaire-grid">
                            @foreach($sectionAnswers as $answer)
                                @php $field=$answer->field;$value=$answer->value;if(is_string($value)&&str_starts_with(trim($value),'[')){ $decoded=json_decode($value,true); if(is_array($decoded))$value=implode(', ',$decoded); } @endphp
                                <div class="answer {{ filled($value)?'':'answer-empty' }}"><div class="label">{{ $field?->label ?? 'Question' }}</div>@if(filled($value))<div class="value">{{ $value }}</div>@endif</div>
                            @endforeach
                        </div>
                    @elseif($sectionName==='Employment History')
                        @php
                            $records=collect([1,2])->mapWithKeys(function($n) use($sectionAnswers){
                                $items=$sectionAnswers->filter(fn($a)=>str_ends_with($a->field?->field_key??'', '_'.$n))->values();
                                return [$n=>$items];
                            })->filter(fn($items)=>$items->contains(fn($a)=>filled($a->value)));
                        @endphp
                        <div class="record-stack">
                            @forelse($records as $recordNo=>$recordAnswers)
                                @php
                                    $byKey=$recordAnswers->keyBy(fn($a)=>$a->field?->field_key);
                                    $start=$byKey->get('employment_dates_'.$recordNo)?->value;
                                    $end=$byKey->get('employment_end_'.$recordNo)?->value;
                                    $current=strtolower((string)($byKey->get('currently_employed_'.$recordNo)?->value))==='yes';
                                    $duration='Duration not available';
                                    try { if($start && ($end||$current)){ $s=\Carbon\Carbon::parse($start);$e=$current?now():\Carbon\Carbon::parse($end);$months=$s->diffInMonths($e);$years=intdiv($months,12);$rem=$months%12;$duration=($years?$years.' yr'.($years>1?'s':''):'').($years&&$rem?' ':'').($rem?$rem.' mo':''); } } catch(\Throwable $e) {}
                                @endphp
                                <div class="record-card"><div class="record-title"><span>Employment Record {{ $recordNo }}</span><span class="duration-badge">{{ $duration }}</span></div><div class="record-grid">
                                    @foreach($recordAnswers as $answer)
                                        @php $field=$answer->field;$value=$answer->value;$wide=str_contains(strtolower($field?->field_key??''),'duties'); @endphp
                                        <div class="answer {{ $wide?'wide':'' }} {{ filled($value)?'':'answer-empty' }}"><div class="label">{{ $field?->label }}</div>@if(filled($value))<div class="value">{{ $value }}</div>@endif</div>
                                    @endforeach
                                </div></div>
                            @empty<div class="empty-note">No employment history provided.</div>@endforelse
                        </div>
                    @elseif($sectionName==='References')
                        @php
                            $records=collect([1,2])->mapWithKeys(function($n) use($sectionAnswers){
                                $items=$sectionAnswers->filter(fn($a)=>str_ends_with($a->field?->field_key??'', '_'.$n))->values(); return [$n=>$items];
                            })->filter(fn($items)=>$items->contains(fn($a)=>filled($a->value)));
                        @endphp
                        <div class="record-stack">
                            @forelse($records as $recordNo=>$recordAnswers)
                                <div class="record-card"><div class="record-title"><span>Professional Reference {{ $recordNo }}</span></div><div class="record-grid">
                                    @foreach($recordAnswers as $answer)<div class="answer {{ filled($answer->value)?'':'answer-empty' }}"><div class="label">{{ $answer->field?->label }}</div>@if(filled($answer->value))<div class="value">{{ $answer->value }}</div>@endif</div>@endforeach
                                </div></div>
                            @empty<div class="empty-note">No professional references provided.</div>@endforelse
                        </div>
                    @else
                        <div class="answer-grid">
                            @foreach($sectionAnswers as $answer)
                                @php $field=$answer->field;$value=$answer->value;if(is_string($value)&&str_starts_with(trim($value),'[')){ $decoded=json_decode($value,true); if(is_array($decoded))$value=implode(', ',$decoded); } @endphp
                                <div class="answer {{ filled($value)?'':'answer-empty' }}"><div class="label">{{ $field?->label ?? 'Field' }}</div>@if(filled($value))<div class="value">{{ $value }}</div>@endif</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-note">No printable answers were submitted on this page.</div>
            @endforelse
        </div>
        <footer class="page-footer"><img src="{{ asset('assets/images/print/redspeed-footer.png') }}" alt="Redspeed Motoworkz OPC Footer"><span class="page-number">Page {{ $pageIndex+1 }} of {{ $printPages->count() }}</span></footer>
    </section>
@endforeach
</div>
</body>
</html>
