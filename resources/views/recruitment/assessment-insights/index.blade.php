@extends('layout.app')
@section('title','Assessment Insights')
@section('page-title','Assessment Insights')
@section('page-subtitle','Automatic, evidence-based candidate ranking and comparison')

@section('content')
@php
    $prefix = request()->routeIs('hr.*') ? 'hr' : 'admin';
    $assessed = $results->filter(fn ($application) => $application->assessmentResult !== null);
    $averageFit = $assessed->count() ? round($assessed->avg(fn ($application) => $application->assessmentResult->overall_score), 1) : 0;
    $averageReliability = $assessed->count() ? round($assessed->avg(fn ($application) => $application->assessmentResult->confidence), 1) : 0;
    $strongFits = $assessed->filter(fn ($application) => $application->assessmentResult->fit_label === 'Strong Fit')->count();

    $fitClass = function (?string $fit) {
        return match ($fit) {
            'Strong Fit' => 'fit-strong',
            'Good Fit' => 'fit-good',
            'Moderate Fit' => 'fit-moderate',
            'Insufficient Evidence' => 'fit-evidence',
            default => 'fit-review',
        };
    };

    $comparisonOrder = [
        'vacancy_qualification_match' => ['Vacancy Qualification Match', '45% of final Role Fit · evidence-supported requirement score'],
        'relevant_experience' => ['Relevant Experience', '30% of supporting fit evidence'],
        'role_specific_skills' => ['Role-Specific Skills', '45% of supporting fit evidence'],
        'problem_solving' => ['Problem Solving & Results', '25% of supporting fit evidence'],
        'role_evidence_quality' => ['Role Evidence Quality', 'Role-questionnaire completeness/specificity only — does not add Role Fit points'],
        'education_certifications' => ['Education', 'Context only — any education requirement is scored once above'],
        'availability_readiness' => ['Availability & Readiness', 'Scheduling context — not part of Role Fit unless explicitly required'],
    ];

    $comparisonData = $results->mapWithKeys(function ($application) use ($comparisonOrder) {
        $result = $application->assessmentResult;
        $scores = collect($result?->category_scores ?? [])->keyBy('key');
        $metrics = [];
        foreach ($comparisonOrder as $key => [$label, $note]) {
            $row = $scores->get($key, []);
            $metrics[$key] = [
                'label' => $label,
                'note' => $note,
                'score' => array_key_exists('score', $row) && $row['score'] !== null ? (float) $row['score'] : null,
                'coverage' => isset($row['coverage']) ? (int) $row['coverage'] : 0,
                'assessed_match_rate' => isset($row['assessed_match_rate']) && $row['assessed_match_rate'] !== null ? (int) $row['assessed_match_rate'] : null,
                'all_qualification_coverage' => isset($row['all_qualification_coverage']) ? (int) $row['all_qualification_coverage'] : null,
                'preference_match_rate' => isset($row['preference_match_rate']) && $row['preference_match_rate'] !== null ? (int) $row['preference_match_rate'] : null,
                'preference_evidence_strength' => isset($row['preference_evidence_strength']) && $row['preference_evidence_strength'] !== null ? (int) $row['preference_evidence_strength'] : null,
                'purpose' => (string) ($row['purpose'] ?? 'fit'),
                'contributes_to_fit' => (bool) ($row['contributes_to_fit'] ?? false),
                'detail' => (string) ($row['detail'] ?? 'No assessable evidence available.'),
                'primary' => (string) data_get($row, 'comparison.primary', ''),
                'secondary' => (string) data_get($row, 'comparison.secondary', ''),
                'facts' => array_values((array) data_get($row, 'comparison.facts', [])),
                'evidence_items' => array_values((array) ($row['evidence_items'] ?? [])),
                'qualification_details' => array_values((array) ($row['qualification_details'] ?? [])),
                'matched' => isset($row['matched_count']) ? (int) $row['matched_count'] : null,
                'not_matched' => isset($row['not_matched_count']) ? (int) $row['not_matched_count'] : null,
                'not_evidenced' => isset($row['not_evidenced_count']) ? (int) $row['not_evidenced_count'] : null,
                'needs_verification' => isset($row['needs_verification_count']) ? (int) $row['needs_verification_count'] : null,
                'preferred_bonus_count' => isset($row['preferred_bonus_count']) ? (int) $row['preferred_bonus_count'] : 0,
                'preferred_bonus_matched' => isset($row['preferred_bonus_matched_count']) ? (int) $row['preferred_bonus_matched_count'] : 0,
                'required_total' => isset($row['required_total_count']) ? (int) $row['required_total_count'] : 0,
                'required_matched' => isset($row['required_matched_count']) ? (int) $row['required_matched_count'] : 0,
                'preferred_total' => isset($row['preferred_total_count']) ? (int) $row['preferred_total_count'] : 0,
                'preferred_matched' => isset($row['preferred_matched_count']) ? (int) $row['preferred_matched_count'] : 0,
                'required_not_matched' => isset($row['required_not_matched_count']) ? (int) $row['required_not_matched_count'] : 0,
                'required_not_evidenced' => isset($row['required_not_evidenced_count']) ? (int) $row['required_not_evidenced_count'] : 0,
                'required_needs_verification' => isset($row['required_needs_verification_count']) ? (int) $row['required_needs_verification_count'] : 0,
                'required_not_verified' => isset($row['required_not_verified_count']) ? (int) $row['required_not_verified_count'] : 0,
                'critical_not_verified' => isset($row['critical_not_verified_count']) ? (int) $row['critical_not_verified_count'] : 0,
            ];
        }

        return [(string) $application->id => [
            'id' => $application->id,
            'name' => $application->applicant?->full_name ?? 'Applicant',
            'reference' => $application->reference_no,
            'fit' => $result?->fit_label ?? 'Not assessed',
            'overall' => $result ? (float) $result->overall_score : null,
            'reliability' => $result ? (int) $result->confidence : null,
            'metrics' => $metrics,
            'strengths' => array_values($result?->strengths ?? []),
            'gaps' => array_values($result?->gaps ?? []),
            'focus' => array_values($result?->interview_focus ?? []),
        ]];
    });
@endphp

<style>
.ai-shell{--ink:#181b22;--muted:#737d8c;--red:#d71920;--red-dark:#9f1016;--line:#e7ebf1;--soft:#f6f8fb;--green:#157347;--amber:#9a6700}
.ai-hero{position:relative;overflow:hidden;border-radius:22px;padding:26px 28px;color:#fff;background:radial-gradient(circle at 91% 10%,rgba(224,34,42,.52),transparent 27%),linear-gradient(124deg,#111319,#22262e 64%,#7f0e14);box-shadow:0 18px 42px rgba(15,20,30,.16)}
.ai-hero:after{content:"";position:absolute;right:-80px;top:-165px;width:330px;height:330px;border:52px solid rgba(255,255,255,.04);border-radius:50%}
.ai-kicker{font-size:10px;font-weight:900;letter-spacing:.17em;color:#ff9ba0}.ai-hero h2{font-size:28px;font-weight:900;letter-spacing:-.04em;margin:.35rem 0}.ai-hero p{margin:0;color:#d1d5dc;max-width:750px;font-size:13px;line-height:1.55}
.ai-select{min-width:330px;background:rgba(255,255,255,.10)!important;color:#fff!important;border:1px solid rgba(255,255,255,.20)!important;border-radius:12px}.ai-select option{color:#111}
.metric,.panel,.rank-card{background:#fff;border:1px solid var(--line);box-shadow:0 7px 24px rgba(17,24,39,.04)}.metric{border-radius:17px;padding:16px 18px;height:100%}.metric .l{font-size:9px;font-weight:900;letter-spacing:.09em;text-transform:uppercase;color:var(--muted)}.metric .n{font-size:25px;font-weight:900;letter-spacing:-.04em;color:var(--ink);line-height:1.2}.metric .s{font-size:11px;color:#8b94a2;margin-top:3px}
.readiness{height:100%;border-radius:18px;padding:17px 18px;background:linear-gradient(135deg,#171a20,#262b34);color:#fff;box-shadow:0 10px 26px rgba(17,24,39,.12)}.readiness-status{font-size:10px;color:#ff8f94;font-weight:900;letter-spacing:.09em;text-transform:uppercase}.readiness-title{font-size:16px;font-weight:850}.readiness-copy{font-size:11px;color:#aeb5c0;line-height:1.45}.readiness-ring{width:68px;height:68px;flex:0 0 68px;border-radius:50%;display:grid;place-items:center;position:relative;background:conic-gradient(#ef3a40 calc(var(--ready)*1%),rgba(255,255,255,.12) 0)}.readiness-ring:after{content:"";position:absolute;inset:7px;background:#1e2229;border-radius:50%}.readiness-ring b{position:relative;z-index:2;font-size:15px}
.ai-tabs{display:flex;gap:6px;flex-wrap:wrap;padding:5px;background:#f0f3f7;border-radius:14px;width:max-content;max-width:100%}.ai-tabs .nav-link{border:0;border-radius:10px;padding:9px 14px;color:#687385;font-size:12px;font-weight:850}.ai-tabs .nav-link.active{background:#fff;color:#171a20;box-shadow:0 3px 10px rgba(17,24,39,.08)}
.panel{border-radius:20px;padding:20px}.section-kicker{font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#8b94a2}.section-title{font-size:18px;font-weight:900;letter-spacing:-.025em;color:#20242c}.section-copy{font-size:12px;color:#7c8695;line-height:1.5}
.auto-chip{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 9px;background:#e9f8ef;color:#157347;font-size:10px;font-weight:900}.soft-chip{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:5px 8px;background:#f1f3f6;color:#657083;font-size:10px;font-weight:800}
.rank-card{border-radius:18px;padding:16px 17px;margin-bottom:10px}.rank-no{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;background:#f1f3f6;color:#414956;font-weight:900;flex:0 0 38px}.rank-no.top{background:#171a20;color:#fff}.candidate-name{font-weight:900;color:#1d2128;line-height:1.25}.candidate-meta{font-size:11px;color:#8a93a1}.fit{display:inline-flex;align-items:center;width:max-content;padding:5px 8px;border-radius:999px;font-size:9px;font-weight:900}.fit-strong{background:#e9f8ef;color:#157347}.fit-good{background:#eaf2ff;color:#255fb0}.fit-moderate{background:#fff5da;color:#916000}.fit-review{background:#fdecec;color:#b4232a}.fit-evidence{background:#f0f2f5;color:#596273}.required-gap-toggle{line-height:1.35!important}.required-gap-toggle:hover{text-decoration:underline!important}.required-gap-panel{border-radius:14px;padding:12px 14px;border:1px solid #e6eaf0;background:#fbfcfd}.required-gap-panel-danger{border-color:#f1c7ca;background:#fff7f7}.required-gap-panel-warning{border-color:#f0dba9;background:#fffbef}.required-gap-title{font-size:11px;font-weight:900;color:#343b46;margin-bottom:3px}.required-gap-panel-danger .required-gap-title{color:#a61b22}.required-gap-item{padding:9px 0;border-top:1px solid rgba(116,126,143,.14)}.required-gap-item:first-of-type{border-top:0}
.score-ring{width:62px;height:62px;border-radius:50%;display:grid;place-items:center;position:relative;background:conic-gradient(var(--red) calc(var(--score)*1%),#eceff3 0)}.score-ring:after{content:"";position:absolute;inset:6px;background:#fff;border-radius:50%}.score-ring b{position:relative;z-index:2;font-size:15px}.score-label{font-size:9px;color:#8a93a1;text-transform:uppercase;font-weight:900}
.signal-title{display:flex;justify-content:space-between;gap:8px;font-size:10px;color:#717b8b;margin-bottom:5px}.signal-title b{color:#303640}.mini-bar{height:6px;background:#eef1f4;border-radius:999px;overflow:hidden}.mini-bar span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#aa1016,#e22b32)}.na-bar span{width:0!important}.na-value{color:#98a1ad!important}
.insight-box{background:#f8fafc;border:1px solid #e9edf3;border-radius:15px;padding:14px}.insight-heading{font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.07em;color:#6e7888}.insight-list{list-style:none;margin:0;padding:0}.insight-list li{font-size:12px;line-height:1.45;color:#4f5968;padding:7px 0;border-bottom:1px solid #edf0f4}.insight-list li:last-child{border:0}.score-table td,.score-table th{font-size:11px;vertical-align:middle}.score-table th{color:#717b8a;font-weight:800}.score-table td{color:#3e4652}.coverage{font-size:9px;font-weight:850;padding:4px 7px;border-radius:999px;background:#eef1f4;color:#657083}
.qualification-list{border-top:1px solid #e9edf2;margin-top:11px;padding-top:10px}.qualification-item{display:grid;grid-template-columns:minmax(200px,1.4fr) 120px minmax(220px,2fr);gap:12px;align-items:start;padding:10px 0;border-bottom:1px solid #edf0f3}.qualification-item:last-child{border-bottom:0}.qualification-name{font-size:11px;font-weight:850;color:#303741;line-height:1.4}.qualification-evidence{font-size:10px;color:#737e8d;line-height:1.45}.q-status{display:inline-flex;align-items:center;justify-content:center;width:max-content;padding:5px 8px;border-radius:999px;font-size:9px;font-weight:900}.q-matched{background:#e5f6ec;color:#157347}.q-not-matched{background:#fde7e7;color:#b4232a}.q-not-evidenced{background:#f0f2f5;color:#657083}.q-needs-verification{background:#fff3d6;color:#8a5a00}
.compare-controls{background:#f8fafc;border:1px solid #e8edf3;border-radius:17px;padding:14px}.compare-table{min-width:980px}.compare-table th{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#717b8b;background:#f6f8fb}.compare-table td{vertical-align:top;padding:13px}.compare-metric-name{font-size:12px;font-weight:900;color:#252a32}.compare-metric-note{font-size:10px;color:#9098a4}.compare-score{font-size:19px;font-weight:900}.compare-detail{font-size:10px;color:#687385;line-height:1.45;margin-top:4px}.compare-secondary{font-size:10px;color:#7f8998;line-height:1.45;margin-top:2px}.compare-cell.stronger{background:#f1fbf5}.compare-cell.stronger .compare-score{color:#157347}.compare-cell.weaker{background:#fff5f5}.compare-cell.weaker .compare-score{color:#b4232a}.compare-cell.same,.compare-cell.neutral{background:#fff}.compare-badge{display:inline-flex;padding:4px 7px;border-radius:999px;font-size:9px;font-weight:900;margin-left:5px}.compare-cell.stronger .compare-badge{background:#daf2e4;color:#157347}.compare-cell.weaker .compare-badge{background:#f9dddd;color:#b4232a}.compare-cell.same .compare-badge,.compare-cell.neutral .compare-badge{background:#edf0f3;color:#657083}.compare-facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:5px;margin-top:9px}.compare-fact{border:1px solid rgba(215,220,228,.9);background:rgba(255,255,255,.82);border-radius:8px;padding:6px 7px;min-width:0}.compare-fact span{display:block;font-size:8px;font-weight:900;text-transform:uppercase;letter-spacing:.045em;color:#919aa7;margin-bottom:2px}.compare-fact b{display:block;font-size:9px;line-height:1.35;color:#414956;overflow-wrap:anywhere}.qualification-breakdown-row>td{padding:0!important;background:#fbfcfe!important}.qualification-breakdown{padding:12px 14px 14px}.qualification-breakdown-title{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:8px}.qualification-breakdown-title b{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#4e5867}.qualification-breakdown-title span{font-size:9px;color:#8b94a2}.qualification-compare-grid{display:grid;grid-template-columns:minmax(250px,.95fr) minmax(300px,1fr) minmax(300px,1fr);border:1px solid #e4e9ef;border-bottom:0}.qualification-compare-grid:last-child{border-bottom:1px solid #e4e9ef}.qualification-compare-grid>div{padding:9px 10px;border-right:1px solid #e4e9ef;min-width:0}.qualification-compare-grid>div:last-child{border-right:0}.qualification-grid-head{background:#f4f6f9;font-size:8px;text-transform:uppercase;letter-spacing:.06em;font-weight:900;color:#788291}.qualification-grid-row{background:#fff;border-top:0}.qualification-requirement{font-size:10px;font-weight:850;color:#343b46;line-height:1.38}.qualification-meta{font-size:8px;color:#919aa7;margin-top:4px}.qualification-status{display:inline-flex;align-items:center;border-radius:999px;padding:3px 6px;font-size:8px;font-weight:900;margin-bottom:5px}.qualification-status.matched{background:#e4f5eb;color:#157347}.qualification-status.not-matched{background:#fde7e7;color:#b4232a}.qualification-status.not-evidenced{background:#eef1f4;color:#657083}.qualification-status.needs-verification{background:#fff3d6;color:#8a5a00}.qualification-evidence-label{font-size:8px;text-transform:uppercase;letter-spacing:.045em;color:#9aa2ae;font-weight:900}.qualification-evidence-text{font-size:9px;line-height:1.4;color:#525c6b;margin-top:2px;overflow-wrap:anywhere}.qualification-advantage{display:inline-flex;margin-top:5px;border-radius:999px;padding:3px 6px;background:#f1f3f6;color:#6a7483;font-size:8px;font-weight:850}.qualification-advantage.a,.qualification-advantage.b{background:#e9f8ef;color:#157347}
.decision-summary{border-radius:16px;border:1px solid #e4e9ef;background:#fff;padding:15px}.decision-summary .summary-line{font-size:12px;color:#4d5664;padding:7px 0;border-bottom:1px solid #eef1f4}.decision-summary .summary-line:last-child{border:0}
.method-pillar{border:1px solid #e5eaf0;border-radius:17px;padding:16px;height:100%;background:#fff}.pillar-percent{font-size:30px;font-weight:900;letter-spacing:-.05em;color:#1d2128}.pillar-title{font-size:13px;font-weight:900}.pillar-copy{font-size:11px;color:#7c8695;line-height:1.45}.criterion-row{display:grid;grid-template-columns:minmax(180px,1fr) 65px 95px 2fr;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid #edf0f3}.criterion-row:last-child{border:0}.criterion-name{font-size:12px;font-weight:900;color:#2a3039}.criterion-weight{font-size:12px;font-weight:900;color:#b1151b}.criterion-desc{font-size:11px;color:#747f8f;line-height:1.4}.fixed-form{border:1px solid #e5eaf0;border-radius:15px;padding:14px;background:#fff}.fixed-form .name{font-size:12px;font-weight:900}.fixed-form .meta{font-size:10px;color:#818b9a}.principle{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #edf0f3;font-size:11px;color:#555f6e}.principle:last-child{border:0}.principle i{color:#157347;margin-top:1px}
.empty-state{padding:45px 20px;text-align:center;border:1px dashed #d7dde6;background:#fbfcfd;border-radius:18px;color:#7a8493}.empty-icon{width:54px;height:54px;display:grid;place-items:center;border-radius:17px;background:#f0f3f6;color:#677181;font-size:23px;margin:0 auto 12px}
@media(max-width:991px){.ai-select{min-width:100%;margin-top:16px}.criterion-row{grid-template-columns:1fr 60px 90px}.criterion-desc{grid-column:1/-1}.rank-signals{margin-top:14px}.score-side{margin-top:14px;justify-content:flex-start!important}}
@media(max-width:767px){.qualification-item{grid-template-columns:1fr}.qualification-evidence{margin-top:-4px}.compare-facts{grid-template-columns:1fr}}
@media(max-width:575px){.ai-hero{padding:22px 18px}.ai-hero h2{font-size:24px}.metric{padding:14px}.ai-tabs{width:100%}.ai-tabs .nav-link{flex:1;padding:8px 9px}.panel{padding:15px}.criterion-row{grid-template-columns:1fr 60px}.criterion-row .importance{grid-column:1/-1}}
</style>

<div class="ai-shell">
    <div class="ai-hero mb-3">
        <div class="row align-items-center position-relative" style="z-index:2">
            <div class="col-lg-8">
                <div class="ai-kicker">RS8 TALENT INTELLIGENCE</div>
                <h2>Automatic Assessment Insights</h2>
                <p>HR only maintains the vacancy qualifications and reviews applicant evidence. Criteria, weights, fixed forms, evidence mapping, scoring, ranking, and comparison are handled automatically.</p>
            </div>
            <div class="col-lg-4">
                <form method="get">
                    <select class="form-select ai-select ms-lg-auto" name="vacancy" onchange="this.form.submit()" aria-label="Select vacancy">
                        @forelse($vacancies as $vacancy)
                            <option value="{{ $vacancy->id }}" @selected($selected?->id === $vacancy->id)>
                                {{ $vacancy->title }} · {{ $vacancy->applications_count }} applicant{{ $vacancy->applications_count === 1 ? '' : 's' }}
                            </option>
                        @empty
                            <option>No vacancies available</option>
                        @endforelse
                    </select>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 py-2 px-3 small">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 py-2 px-3 small">{{ $errors->first() }}</div>
    @endif

    @if(!$selected)
        <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-briefcase"></i></div>
            <div class="fw-bold text-dark">No vacancy available</div>
            <div class="small mt-1">Create a job vacancy first. Assessment Insights will configure itself automatically.</div>
        </div>
    @else
        <div class="row g-2 mb-3">
            <div class="col-6 col-xl-2">
                <div class="metric"><div class="l">Applicants</div><div class="n">{{ $results->count() }}</div><div class="s">in this vacancy</div></div>
            </div>
            <div class="col-6 col-xl-2">
                <div class="metric"><div class="l">Strong Fits</div><div class="n">{{ $strongFits }}</div><div class="s">high-fit evidence</div></div>
            </div>
            <div class="col-6 col-xl-2">
                <div class="metric"><div class="l">Average Fit</div><div class="n">{{ number_format($averageFit, 1) }}%</div><div class="s">role-fit score</div></div>
            </div>
            <div class="col-6 col-xl-2">
                <div class="metric"><div class="l">Overall Evidence Quality</div><div class="n">{{ number_format($averageReliability, 1) }}%</div><div class="s">assessment-wide completeness & specificity</div></div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="readiness d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <div class="readiness-status">Automatic Assessment</div>
                        <div class="readiness-title">
                            @if($readiness['ready'] ?? false) Ready for ranking
                            @elseif(!($readiness['qualification_ready'] ?? false)) Add vacancy qualifications
                            @else Fixed-form evidence check needed
                            @endif
                        </div>
                        <div class="readiness-copy mt-1">
                            {{ $readiness['qualification_count'] ?? 0 }} qualification{{ ($readiness['qualification_count'] ?? 0) === 1 ? '' : 's' }} ·
                            {{ $readiness['criteria_count'] ?? 0 }} scoring areas ·
                            {{ $readiness['templates_count'] ?? 0 }} fixed form{{ ($readiness['templates_count'] ?? 0) === 1 ? '' : 's' }}
                        </div>
                    </div>
                    <div class="readiness-ring" style="--ready:{{ $readiness['score'] ?? 0 }}"><b>{{ $readiness['score'] ?? 0 }}%</b></div>
                </div>
            </div>
        </div>

        @if(!($readiness['forms_ready'] ?? false))
            <div class="alert alert-warning rounded-4 border-0 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                The fixed application forms are missing required assessment field keys{{ !empty($readiness['missing_fields']) ? ': '.implode(', ', $readiness['missing_fields']) : '.' }}
                Assessment remains conservative until those fields are restored.
            </div>
        @endif
        @if(!($readiness['qualification_ready'] ?? false))
            <div class="alert alert-warning rounded-4 border-0 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                This vacancy has no qualification baseline. Add qualifications in Job Vacancies; the system will structure and score them automatically.
            </div>
        @endif

        <ul class="nav ai-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rankingPane" type="button"><i class="bi bi-trophy me-1"></i> Candidate Ranking</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#comparisonPane" type="button"><i class="bi bi-columns-gap me-1"></i> Compare 2 Candidates</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#methodPane" type="button"><i class="bi bi-shield-check me-1"></i> Assessment Method</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="rankingPane">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                    <div>
                        <div class="section-kicker">Evidence-based ranking</div>
                        <div class="section-title">Candidate Ranking</div>
                        <div class="section-copy">45% required-qualification baseline + 55% supporting role evidence. Preferred / nice-to-have qualifications are shown as optional advantages and tie-breakers, not hard penalties. Missing or unverified requirements stay visibly separate from confirmed failures and never receive artificial points.</div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="auto-chip"><i class="bi bi-lightning-charge-fill"></i> Auto-scored</span>
                        <form method="post" action="{{ route($prefix.'.assessment-insights.recalculate') }}">
                            @csrf
                            <input type="hidden" name="vacancy_id" value="{{ $selected->id }}">
                            <button class="btn btn-sm btn-outline-dark rounded-3"><i class="bi bi-arrow-clockwise me-1"></i> Refresh Scores</button>
                        </form>
                    </div>
                </div>

                @forelse($results as $index => $application)
                    @php
                        $result = $application->assessmentResult;
                        $scoreRows = collect($result?->category_scores ?? [])->keyBy('key');
                        $qualification = $scoreRows->get('vacancy_qualification_match', []);
                        $experience = $scoreRows->get('relevant_experience', []);
                        $skills = $scoreRows->get('role_specific_skills', []);
                        $problem = $scoreRows->get('problem_solving', []);
                        $requiredFailed = (int) ($qualification['required_not_matched_count'] ?? 0);
                        $requiredPending = (int) ($qualification['required_not_verified_count'] ?? 0);
                        $requiredGapDetails = collect($qualification['qualification_details'] ?? [])->filter(fn ($detail) =>
                            ($detail['level'] ?? 'required') === 'required' && ($detail['status'] ?? '') === 'not_matched'
                        )->values();
                        $requiredPendingDetails = collect($qualification['qualification_details'] ?? [])->filter(fn ($detail) =>
                            ($detail['level'] ?? 'required') === 'required' && in_array(($detail['status'] ?? ''), ['not_evidenced','needs_verification'], true)
                        )->values();
                    @endphp
                    <div class="rank-card">
                        <div class="row align-items-center g-3">
                            <div class="col-xl-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rank-no {{ $index === 0 ? 'top' : '' }}">#{{ $index + 1 }}</div>
                                    <div class="min-w-0">
                                        <div class="candidate-name text-truncate">{{ $application->applicant?->full_name ?? 'Applicant' }}</div>
                                        <div class="candidate-meta">{{ $application->reference_no }} · {{ $application->status }}</div>
                                        <div class="fit {{ $fitClass($result?->fit_label) }} mt-2">{{ $result?->fit_label ?? 'Not assessed' }}</div>
                                        @if($requiredFailed > 0)
                                            <button class="btn btn-link candidate-meta mt-1 text-danger fw-semibold p-0 text-decoration-none text-start required-gap-toggle"
                                                    type="button" data-bs-toggle="collapse" data-bs-target="#requiredGaps{{ $application->id }}"
                                                    aria-expanded="false" aria-controls="requiredGaps{{ $application->id }}">
                                                <i class="bi bi-exclamation-octagon me-1"></i>{{ $requiredFailed }} confirmed required gap{{ $requiredFailed === 1 ? '' : 's' }}
                                                <span class="ms-1">— view exact requirement <i class="bi bi-chevron-down"></i></span>
                                            </button>
                                        @elseif($requiredPending > 0)
                                            <button class="btn btn-link candidate-meta mt-1 text-warning-emphasis fw-semibold p-0 text-decoration-none text-start required-gap-toggle"
                                                    type="button" data-bs-toggle="collapse" data-bs-target="#requiredPending{{ $application->id }}"
                                                    aria-expanded="false" aria-controls="requiredPending{{ $application->id }}">
                                                <i class="bi bi-question-circle me-1"></i>{{ $requiredPending }} required item{{ $requiredPending === 1 ? '' : 's' }} pending verification
                                                <span class="ms-1">— view exact requirement <i class="bi bi-chevron-down"></i></span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 rank-signals">
                                @foreach([
                                    ['Vacancy Qualifications', $qualification],
                                    ['Relevant Experience', $experience],
                                    ['Role-Specific Skills', $skills],
                                    ['Problem Solving', $problem],
                                ] as [$label, $row])
                                    @php $value = array_key_exists('score', $row) ? $row['score'] : null; @endphp
                                    <div class="mb-2">
                                        <div class="signal-title"><span>{{ $label }}</span><b class="{{ $value === null ? 'na-value' : '' }}">{{ $value === null ? 'N/A' : round($value).'%' }}</b></div>
                                        <div class="mini-bar {{ $value === null ? 'na-bar' : '' }}"><span style="width:{{ $value === null ? 0 : max(0,min(100,$value)) }}%"></span></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="col-xl-3">
                                <div class="d-flex justify-content-xl-end align-items-center gap-3 score-side">
                                    <div class="text-end">
                                        <div class="score-label">Overall Evidence Quality</div>
                                        <div class="fw-bold">{{ $result?->confidence ?? 0 }}%</div>
                                        <div class="candidate-meta mt-1">Applicant-declared; HR verification separate</div>
                                        <button class="btn btn-sm btn-link text-dark text-decoration-none p-0 mt-1 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#insight{{ $application->id }}">View insight <i class="bi bi-chevron-down"></i></button>
                                    </div>
                                    <div class="text-center"><div class="score-ring" style="--score:{{ $result?->overall_score ?? 0 }}"><b>{{ number_format($result?->overall_score ?? 0, 1) }}%</b></div><div class="score-label mt-1">Role Fit</div></div>
                                </div>
                            </div>
                        </div>

                        @if($requiredGapDetails->isNotEmpty())
                            <div class="collapse mt-2" id="requiredGaps{{ $application->id }}">
                                <div class="required-gap-panel required-gap-panel-danger">
                                    <div class="required-gap-title"><i class="bi bi-exclamation-octagon-fill me-1"></i>Confirmed required qualification gap{{ $requiredGapDetails->count() === 1 ? '' : 's' }}</div>
                                    <div class="small text-muted mb-2">These are explicit required requirements the submitted evidence does not meet. HR can audit the exact requirement and evidence below.</div>
                                    @foreach($requiredGapDetails as $gapDetail)
                                        <div class="required-gap-item">
                                            <div class="fw-semibold text-danger">{{ $gapDetail['qualification'] ?? 'Required qualification' }}</div>
                                            <div class="small mt-1"><strong>Why:</strong> {{ $gapDetail['evidence'] ?? 'The submitted evidence does not satisfy this requirement.' }}</div>
                                            @if(!empty($gapDetail['evidence_source']))
                                                <div class="small text-muted mt-1"><strong>Evidence source:</strong> {{ $gapDetail['evidence_source'] }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                    <button class="btn btn-sm btn-outline-danger rounded-3 mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#insight{{ $application->id }}">
                                        Open full assessment insight
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if($requiredPendingDetails->isNotEmpty() && $requiredGapDetails->isEmpty())
                            <div class="collapse mt-2" id="requiredPending{{ $application->id }}">
                                <div class="required-gap-panel required-gap-panel-warning">
                                    <div class="required-gap-title"><i class="bi bi-question-circle-fill me-1"></i>Required qualification pending verification</div>
                                    <div class="small text-muted mb-2">Missing or ambiguous evidence stays separate from a confirmed failure.</div>
                                    @foreach($requiredPendingDetails as $gapDetail)
                                        <div class="required-gap-item">
                                            <div class="fw-semibold">{{ $gapDetail['qualification'] ?? 'Required qualification' }}</div>
                                            <div class="small mt-1"><strong>Status:</strong> {{ ($gapDetail['status'] ?? '') === 'needs_verification' ? 'Needs verification' : 'Not evidenced' }}</div>
                                            <div class="small mt-1"><strong>Why:</strong> {{ $gapDetail['evidence'] ?? 'No sufficient evidence was submitted.' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="collapse mt-3" id="insight{{ $application->id }}">
                            @php $calculation = data_get(collect($result?->category_scores ?? [])->first(), 'calculation', []); @endphp
                            @if($calculation)
                                <div class="alert alert-light border small mb-3">
                                    <strong>Score calculation:</strong>
                                    @if($calculation['qualification_score'] !== null && $calculation['supporting_score'] !== null)
                                        {{ $calculation['qualification_score'] }} × 45% + {{ $calculation['supporting_score'] }} × 55%
                                        = {{ number_format($calculation['raw_overall'], 1) }}%.
                                    @else
                                        Only one scoring block is assessable; its score is shown provisionally.
                                    @endif
                                    @if($calculation['cap_applied'])
                                        Required-qualification gaps/uncertainty limit the final score to {{ number_format($calculation['final_overall'], 1) }}%.
                                    @endif
                                    @if($calculation['supporting_weight_assessed'] < 100)
                                        Supporting evidence covers {{ $calculation['supporting_weight_assessed'] }}/100 weight points.
                                        Missing areas remain N/A; the supporting average uses only assessable areas.
                                    @endif
                                    <div class="mt-1">Assessed {{ $result?->assessed_at?->format('M d, Y g:i A') }}.
                                        Applicant-declared evidence; qualification matches require HR verification.</div>
                                </div>
                            @endif

                            <div class="row g-2">
                                <div class="col-lg-4">
                                    <div class="insight-box h-100">
                                        <div class="insight-heading"><i class="bi bi-check2-circle me-1"></i> Strong Evidence</div>
                                        <ul class="insight-list mt-1">@foreach($result?->strengths ?? [] as $item)<li>{{ $item }}</li>@endforeach</ul>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="insight-box h-100">
                                        <div class="insight-heading"><i class="bi bi-exclamation-circle me-1"></i> Gaps / Verify</div>
                                        <ul class="insight-list mt-1">@foreach($result?->gaps ?? [] as $item)<li>{{ $item }}</li>@endforeach</ul>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="insight-box h-100">
                                        <div class="insight-heading"><i class="bi bi-chat-square-text me-1"></i> Interview Focus</div>
                                        <ul class="insight-list mt-1">@foreach($result?->interview_focus ?? [] as $item)<li>{{ $item }}</li>@endforeach</ul>
                                    </div>
                                </div>
                            </div>

                            <div class="insight-box mt-2">
                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                    <div>
                                        <div class="insight-heading">Assessment breakdown & evidence coverage</div>
                                        <div class="small text-muted">Role Fit rows affect the final score. Role Evidence Quality measures only the role-questionnaire completeness/specificity; Overall Evidence Quality is the assessment-wide indicator. Neither verifies that applicant claims are true. Context rows are shown for HR without adding or removing Role Fit points.</div>
                                    </div>
                                    @if(!empty($qualification))
                                        <div class="d-flex gap-1 flex-wrap">
                                            <span class="soft-chip"><i class="bi bi-check-circle"></i> {{ $qualification['matched_count'] ?? 0 }} matched</span>
                                            <span class="soft-chip"><i class="bi bi-x-circle"></i> {{ $qualification['not_matched_count'] ?? 0 }} not met</span>
                                            <span class="soft-chip"><i class="bi bi-dash-circle"></i> {{ $qualification['not_evidenced_count'] ?? 0 }} not evidenced</span>
                                            <span class="soft-chip"><i class="bi bi-question-circle"></i> {{ $qualification['needs_verification_count'] ?? 0 }} verify</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm score-table mb-0">
                                        <thead><tr><th>Evidence area</th><th>Use</th><th class="text-end">Score</th><th class="text-end">Coverage</th><th>Why</th></tr></thead>
                                        <tbody>
                                        @foreach($result?->category_scores ?? [] as $row)
                                            <tr>
                                                <td class="fw-semibold">{{ $row['name'] ?? 'Evidence' }}</td>
                                                <td><span class="soft-chip">{{ ($row['purpose'] ?? 'fit') === 'fit' ? 'Role Fit' : (($row['purpose'] ?? '') === 'reliability' ? 'Evidence Quality' : 'Context') }}</span></td>
                                                <td class="text-end fw-bold">{{ ($row['score'] ?? null) === null ? 'N/A' : round($row['score']).'%' }}</td>
                                                <td class="text-end"><span class="coverage">{{ $row['coverage'] ?? 0 }}%</span></td>
                                                <td>{{ $row['detail'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if(!empty($qualification['qualification_details']))
                                    <div class="qualification-list">
                                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                            <div>
                                                <div class="insight-heading">Qualification-by-qualification evidence</div>
                                                <div class="small text-muted">The same requirement statuses also appear in head-to-head comparison. Missing proof is separated from an explicit failure.</div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-secondary rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#qualificationEvidence{{ $application->id }}">
                                                Show evidence <i class="bi bi-chevron-down ms-1"></i>
                                            </button>
                                        </div>
                                        <div class="collapse" id="qualificationEvidence{{ $application->id }}">
                                            @foreach($qualification['qualification_details'] as $detail)
                                                @php
                                                    $status = $detail['status'] ?? 'not_evidenced';
                                                    $statusClass = match ($status) {
                                                        'matched' => 'q-matched',
                                                        'not_matched' => 'q-not-matched',
                                                        'needs_verification' => 'q-needs-verification',
                                                        default => 'q-not-evidenced',
                                                    };
                                                    $statusLabel = match ($status) {
                                                        'matched' => 'Matched',
                                                        'not_matched' => 'Not met',
                                                        'needs_verification' => 'Needs verification',
                                                        default => 'Not evidenced',
                                                    };
                                                @endphp
                                                <div class="qualification-item">
                                                    <div>
                                                        <div class="qualification-name">{{ $detail['qualification'] ?? 'Qualification' }}</div>
                                                        <div class="small text-muted mt-1">{{ ucfirst(str_replace('_',' ', $detail['level'] ?? 'required')) }} · {{ ucfirst($detail['importance'] ?? 'medium') }}</div>
                                                    </div>
                                                    <div><span class="q-status {{ $statusClass }}">{{ $statusLabel }}</span></div>
                                                    <div class="qualification-evidence">{{ $detail['evidence'] ?? 'No evidence detail available.' }}
                                                        @if(!empty($detail['component_evidence']))
                                                            <div class="mt-2 d-flex gap-1 flex-wrap">
                                                                @foreach($detail['component_evidence'] as $component)
                                                                    @php
                                                                        $componentStatus = $component['status'] ?? 'not_evidenced';
                                                                        $componentClass = match ($componentStatus) {
                                                                            'matched' => 'q-matched',
                                                                            'not_matched' => 'q-not-matched',
                                                                            'needs_verification' => 'q-needs-verification',
                                                                            default => 'q-not-evidenced',
                                                                        };
                                                                    @endphp
                                                                    <span class="q-status {{ $componentClass }}">{{ $component['label'] ?? 'Component' }}: {{ $componentStatus === 'matched' ? 'evidenced' : ($componentStatus === 'not_matched' ? 'not met' : 'verify') }}</span>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        @if(!empty($detail['preferred_bonus']))
                                                            <div class="mt-1"><strong>Preferred advantage:</strong> {{ $detail['preferred_bonus'] }} — {{ ($detail['preferred_bonus_matched'] ?? false) ? 'evidenced' : 'not evidenced' }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>{{ $result?->summary }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state mt-3">
                        <div class="empty-icon"><i class="bi bi-people"></i></div>
                        <div class="fw-bold text-dark">No applicants yet</div>
                        <div class="small mt-1">New applications will be assessed automatically after submission.</div>
                    </div>
                @endforelse
            </div>

            <div class="tab-pane fade" id="comparisonPane">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <div class="section-kicker">Focused comparison</div>
                            <div class="section-title">Compare Two Candidates</div>
                            <div class="section-copy">Head-to-head evidence is shown per category. Vacancy requirements are broken down one-by-one. Fit rows compare scored job evidence; Role Evidence Quality and Context rows stay clearly separated so HR can see facts without double-counting them.</div>
                        </div>
                        <span class="soft-chip"><i class="bi bi-shield-check"></i> Missing evidence is separate from failure</span>
                    </div>

                    @if($results->count() < 2)
                        <div class="empty-state"><div class="empty-icon"><i class="bi bi-columns-gap"></i></div><div class="fw-bold text-dark">At least two applicants are needed</div><div class="small mt-1">Comparison becomes available automatically when another applicant applies.</div></div>
                    @else
                        <div class="compare-controls mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold mb-1">Candidate A</label>
                                    <select id="compareA" class="form-select form-select-sm">
                                        <option value="">Select Candidate A</option>
                                        @foreach($results as $application)
                                            <option value="{{ $application->id }}">{{ $application->applicant?->full_name ?? 'Applicant' }} · {{ number_format($application->assessmentResult?->overall_score ?? 0, 1) }}%</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold mb-1">Candidate B</label>
                                    <select id="compareB" class="form-select form-select-sm">
                                        <option value="">Select Candidate B</option>
                                        @foreach($results as $index => $application)
                                            <option value="{{ $application->id }}">{{ $application->applicant?->full_name ?? 'Applicant' }} · {{ number_format($application->assessmentResult?->overall_score ?? 0, 1) }}%</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 d-grid"><button type="button" id="runComparison" class="btn btn-danger btn-sm"><i class="bi bi-columns-gap me-1"></i> Compare</button></div>
                            </div>
                            <div id="sameCandidateWarning" class="small text-danger mt-2 d-none"><i class="bi bi-exclamation-circle me-1"></i>Select two different candidates.</div>
                        </div>

                        <div id="comparisonResults" class="d-none">
                            <div class="table-responsive">
                                <table class="table table-bordered compare-table mb-3">
                                    <thead>
                                        <tr>
                                            <th style="width:26%">Evidence area</th>
                                            <th id="compareHeaderA">Candidate A</th>
                                            <th id="compareHeaderB">Candidate B</th>
                                        </tr>
                                    </thead>
                                    <tbody id="comparisonBody"></tbody>
                                </table>
                            </div>
                            <div class="decision-summary">
                                <div class="insight-heading mb-1">Key Differences</div>
                                <div id="comparisonSummary"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="tab-pane fade" id="methodPane">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <div class="section-kicker">No HR/Admin setup required</div>
                            <div class="section-title">Automatic Assessment Method</div>
                            <div class="section-copy">The assessment model is fixed and transparent so applicants in the same vacancy are evaluated by the same job-related rules, with uncertainty kept separate from failure.</div>
                        </div>
                        <span class="auto-chip"><i class="bi bi-lock-fill"></i> Automatic configuration</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="method-pillar">
                                <div class="pillar-percent">{{ $definition['qualification_weight'] ?? 45 }}%</div>
                                <div class="pillar-title">Vacancy Qualification Match</div>
                                <div class="pillar-copy mt-1">Direct comparison against HR's vacancy qualifications. Required and critical items form the scored baseline. Preferred / nice-to-have items remain optional advantages/tie-breakers, while unverified required evidence stays separate from confirmed failure.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="method-pillar">
                                <div class="pillar-percent">{{ $definition['supporting_weight'] ?? 55 }}%</div>
                                <div class="pillar-title">Supporting Role Evidence</div>
                                <div class="pillar-copy mt-1">Only Relevant Experience, Role-Specific Skills, and Problem Solving contribute to this 55% block. Overall Evidence Quality summarizes assessment-wide completeness/specificity; Role Evidence Quality covers only role-focused answers. Neither substitutes for HR verification; education and availability are context unless the vacancy explicitly makes them requirements.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-xl-7">
                            <div class="insight-heading mb-1">Supporting model: fit vs evidence quality/context</div>
                            @foreach($definition['criteria'] ?? [] as $criterion)
                                <div class="criterion-row">
                                    <div class="criterion-name">{{ $criterion['name'] }}</div>
                                    <div class="criterion-weight">{{ ($criterion['purpose'] ?? 'fit') === 'fit' ? $criterion['weight'] . '%' : (($criterion['purpose'] ?? '') === 'reliability' ? 'Role Evidence Quality' : 'Context') }}</div>
                                    <div class="importance"><span class="soft-chip">{{ ucfirst($criterion['importance']) }}</span></div>
                                    <div class="criterion-desc">{{ $criterion['description'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-xl-5">
                            <div class="insight-heading mb-2">Fixed forms used automatically</div>
                            <div class="row g-2 mb-3">
                                @forelse($templates as $template)
                                    <div class="col-12">
                                        <div class="fixed-form d-flex justify-content-between align-items-center gap-2">
                                            <div><div class="name">{{ $template->name }}</div><div class="meta">{{ ucfirst($template->type) }} · {{ $template->fields->count() }} fields</div></div>
                                            <span class="auto-chip"><i class="bi bi-link-45deg"></i> Connected</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="small text-danger">Required fixed forms were not found.</div>
                                @endforelse
                            </div>

                            <div class="insight-heading mb-1">Evidence quality & verification rules</div>
                            <div>
                                @foreach($definition['principles'] ?? [] as $principle)
                                    <div class="principle"><i class="bi bi-check-circle-fill"></i><span>{{ $principle }}</span></div>
                                @endforeach
                                <div class="principle"><i class="bi bi-check-circle-fill"></i><span>Assessment is recalculated automatically after a new application, an HR edit to applicant evidence, or a vacancy qualification update.</span></div>
                                <div class="principle"><i class="bi bi-check-circle-fill"></i><span>Scores support HR decisions; interview and document verification remain required before final hiring decisions.</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="small text-muted mt-3"><i class="bi bi-shield-check me-1"></i>Assessment Insights is decision support only. It does not automatically hire or reject applicants.</div>
    @endif
</div>
@endsection

@push('scripts')
@if($selected && $results->count() >= 2)
<script>
(function(){
    const candidates = @json($comparisonData);
    const metricOrder = @json(array_keys($comparisonOrder));
    const metricLabels = @json($comparisonOrder);
    const compareA = document.getElementById('compareA');
    const compareB = document.getElementById('compareB');
    const body = document.getElementById('comparisonBody');
    const summary = document.getElementById('comparisonSummary');
    const warning = document.getElementById('sameCandidateWarning');

    const esc = value => String(value ?? '')
        .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
        .replaceAll('"','&quot;').replaceAll("'",'&#039;');
    const text = (value, fallback = 'Not provided') => {
        const clean = String(value ?? '').trim();
        return clean || fallback;
    };
    const short = (value, max = 150) => {
        const clean = text(value, 'No evidence captured.').replace(/\s+/g, ' ');
        return clean.length > max ? `${clean.slice(0, max - 3)}...` : clean;
    };

    function scoreText(score){ return score === null || score === undefined ? 'N/A' : `${Number(score).toFixed(1)}%`; }
    function statusLabel(status){
        if(status === 'matched') return 'Matched';
        if(status === 'not_matched') return 'Not met';
        if(status === 'needs_verification') return 'Needs verification';
        return 'Not evidenced';
    }
    function statusClass(status){
        if(status === 'matched') return 'matched';
        if(status === 'not_matched') return 'not-matched';
        if(status === 'needs_verification') return 'needs-verification';
        return 'not-evidenced';
    }
    function statusRank(status){
        if(status === 'matched') return 3;
        if(status === 'needs_verification') return 2;
        if(status === 'not_evidenced') return 1;
        return 0;
    }

    function factsHtml(metric){
        const facts = Array.isArray(metric?.facts) ? metric.facts.filter(item => item && text(item.value, '') !== '') : [];
        if(!facts.length) return '';
        return `<div class="compare-facts">${facts.map(item => `<div class="compare-fact"><span>${esc(item.label || 'Evidence')}</span><b>${esc(item.value)}</b></div>`).join('')}</div>`;
    }

    function cell(metric, relation, delta = 0){
        const relationClass = relation || 'neutral';
        let badge = 'N/A';
        if(metric.purpose === 'reliability'){
            badge = 'Role evidence quality only';
        } else if(metric.purpose === 'context'){
            badge = 'Context only';
        } else if(metric.score !== null && metric.score !== undefined){
            if(relation === 'stronger') badge = `Higher +${delta.toFixed(1)}`;
            else if(relation === 'weaker') badge = `Lower -${delta.toFixed(1)}`;
            else badge = relation === 'same' ? 'Same score' : 'Not comparable';
        }
        let qualifier = '';
        if(metric.matched !== null && metric.matched !== undefined){
            const requiredTotal = Number(metric.required_total || 0);
            if(requiredTotal > 0){
                qualifier = `<div class="compare-detail"><b>Required: ${metric.required_matched || 0}/${requiredTotal} met</b> · ${metric.required_not_matched || 0} not met · ${metric.required_not_evidenced || 0} not evidenced · ${metric.required_needs_verification || 0} verify</div>`;
                if(metric.assessed_match_rate !== null && metric.assessed_match_rate !== undefined){
                    qualifier += `<div class="compare-secondary">${metric.assessed_match_rate}% match among assessed required qualifications · ${metric.coverage ?? 0}% required evidence coverage</div>`;
                }
            } else {
                const total = Number(metric.matched || 0) + Number(metric.not_matched || 0) + Number(metric.not_evidenced || 0) + Number(metric.needs_verification || 0);
                qualifier = `<div class="compare-detail"><b>${metric.matched}/${total} matched</b> · ${metric.not_matched || 0} not met · ${metric.not_evidenced || 0} not evidenced · ${metric.needs_verification || 0} verify</div>`;
            }
            if(Number(metric.preferred_total || 0) > 0){
                qualifier += `<div class="compare-secondary">Preferred / nice-to-have: ${metric.preferred_matched || 0}/${metric.preferred_total} evidenced · tie-breaker only; missing optional evidence does not reduce Role Fit</div>`;
            }
            if(Number(metric.preferred_bonus_count || 0) > 0){
                qualifier += `<div class="compare-secondary">Embedded preferred advantage: ${metric.preferred_bonus_matched || 0}/${metric.preferred_bonus_count} evidenced</div>`;
            }
        }
        const primary = metric.primary ? `<div class="compare-detail fw-semibold text-dark">${esc(metric.primary)}</div>` : '';
        const secondary = metric.secondary ? `<div class="compare-secondary">${esc(metric.secondary)}</div>` : '';
        const footer = metric.purpose === 'context'
            ? `Context completeness ${metric.coverage ?? 0}% · not scored`
            : metric.purpose === 'reliability'
                ? `Role-evidence coverage ${metric.coverage ?? 0}% · not Role Fit points`
                : metric.purpose === 'overall'
                    ? `Overall Evidence Quality ${metric.coverage ?? 0}%`
                    : `Evidence coverage ${metric.coverage ?? 0}%`;
        return `<td class="compare-cell ${relationClass}"><div><span class="compare-score">${scoreText(metric.score)}</span><span class="compare-badge">${badge}</span></div>${primary}${secondary}${qualifier}${factsHtml(metric)}<div class="compare-detail">${footer}</div></td>`;
    }

    function qualificationBreakdown(ma, mb){
        const aRows = Array.isArray(ma?.qualification_details) ? ma.qualification_details : [];
        const bRows = Array.isArray(mb?.qualification_details) ? mb.qualification_details : [];
        const byA = new Map(aRows.map(row => [text(row.qualification, ''), row]));
        const byB = new Map(bRows.map(row => [text(row.qualification, ''), row]));
        const requirements = [...new Set([...byA.keys(), ...byB.keys()].filter(Boolean))];
        if(!requirements.length) return '';

        const rows = requirements.map((requirement, index) => {
            const qa = byA.get(requirement) || {};
            const qb = byB.get(requirement) || {};
            const baseRankA = statusRank(qa.status);
            const baseRankB = statusRank(qb.status);
            const isOptional = ['preferred','nice_to_have'].includes(String(qa.level || qb.level || ''));
            const optionalStrengthA = Number(qa.preference_evidence_strength ?? 0);
            const optionalStrengthB = Number(qb.preference_evidence_strength ?? 0);
            const rankA = baseRankA + (qa.preferred_bonus_matched === true ? 0.25 : 0);
            const rankB = baseRankB + (qb.preferred_bonus_matched === true ? 0.25 : 0);
            const preferenceDiff = baseRankA === baseRankB && qa.preferred_bonus && qb.preferred_bonus && qa.preferred_bonus_matched !== qb.preferred_bonus_matched;
            const optionalBreadthDiff = isOptional && baseRankA === baseRankB && baseRankA === statusRank('matched') && optionalStrengthA !== optionalStrengthB;
            let advantage = rankA === rankB ? 'Same status' : (rankA > rankB ? (preferenceDiff ? 'A has preferred advantage' : 'A has advantage') : (preferenceDiff ? 'B has preferred advantage' : 'B has advantage'));
            let advantageClass = rankA === rankB ? '' : (rankA > rankB ? 'a' : 'b');
            if(optionalBreadthDiff){
                advantage = optionalStrengthA > optionalStrengthB ? 'A has broader optional evidence' : 'B has broader optional evidence';
                advantageClass = optionalStrengthA > optionalStrengthB ? 'a' : 'b';
            }
            const meta = [qa.level || qb.level, qa.importance || qb.importance].filter(Boolean).map(value => String(value).replaceAll('_',' ')).join(' · ');
            const evidenceCell = q => {
                const metric = q.metric_label ? `<div class="qualification-meta">${esc(q.metric_label)}</div>` : '';
                const preferred = q.preferred_bonus
                    ? `<div class="qualification-meta"><b>Preferred advantage:</b> ${esc(q.preferred_bonus)} — ${q.preferred_bonus_matched === true ? 'evidenced' : 'not evidenced'}</div>`
                    : '';
                const components = Array.isArray(q.component_evidence) && q.component_evidence.length
                    ? `<div class="qualification-meta mt-1"><b>Components:</b> ${q.component_evidence.map(c => `${esc(c.label || 'Component')}: ${esc(statusLabel(c.status))}`).join(' · ')}</div>`
                    : '';
                const related = q.related_field_match === true
                    ? `<div class="qualification-meta mt-1"><b>Related-field rule:</b> accepted by the vacancy wording; document verification remains separate.</div>`
                    : '';
                const optionalDepth = ['preferred','nice_to_have'].includes(String(q.level || '')) && q.preference_evidence_strength !== null && q.preference_evidence_strength !== undefined
                    ? `<div class="qualification-meta mt-1"><b>Optional evidence:</b> ${esc(q.preference_evidence_summary || 'tie-breaker context only')}</div>`
                    : '';
                return `<span class="qualification-status ${statusClass(q.status)}">${statusLabel(q.status)}</span>${metric}<div class="qualification-evidence-label">Evidence used</div><div class="qualification-evidence-text">${esc(short(q.evidence, 220))}</div>${components}${related}${optionalDepth}${preferred}`;
            };
            return `<div class="qualification-compare-grid qualification-grid-row"><div><div class="qualification-requirement">${index + 1}. ${esc(requirement)}</div><div class="qualification-meta">${esc(meta || 'Vacancy qualification')}</div><span class="qualification-advantage ${advantageClass}">${advantage}</span></div><div>${evidenceCell(qa)}</div><div>${evidenceCell(qb)}</div></div>`;
        }).join('');

        return `<tr class="qualification-breakdown-row"><td colspan="3"><div class="qualification-breakdown"><div class="qualification-breakdown-title"><b>Qualification-by-qualification evidence</b><span>${requirements.length} requirement(s) · exact evidence used by the assessment</span></div><div class="qualification-compare-grid qualification-grid-head"><div>Vacancy requirement</div><div>Candidate A evidence</div><div>Candidate B evidence</div></div>${rows}</div></td></tr>`;
    }

    function relationPair(ma, mb){
        if((ma.purpose && !['fit','overall'].includes(ma.purpose)) || (mb.purpose && !['fit','overall'].includes(mb.purpose))){
            return ['neutral','neutral',0];
        }
        if(ma.score === null || ma.score === undefined || mb.score === null || mb.score === undefined){
            return ['neutral','neutral',0];
        }
        const aScore = Number(ma.score), bScore = Number(mb.score);
        if(aScore === bScore) return ['same','same',0];
        const delta = Math.abs(aScore - bScore);
        return aScore > bScore ? ['stronger','weaker',delta] : ['weaker','stronger',delta];
    }

    function qualificationDifferences(ma, mb){
        const aRows = Array.isArray(ma?.qualification_details) ? ma.qualification_details : [];
        const bRows = Array.isArray(mb?.qualification_details) ? mb.qualification_details : [];
        const byB = new Map(bRows.map(row => [text(row.qualification, ''), row]));
        return aRows.map(qa => ({qa, qb: byB.get(text(qa.qualification, '')) || {}}))
            .filter(pair => pair.qa.status !== pair.qb.status
                || (pair.qa.preferred_bonus && pair.qb.preferred_bonus && pair.qa.preferred_bonus_matched !== pair.qb.preferred_bonus_matched)
                || (['preferred','nice_to_have'].includes(String(pair.qa.level || pair.qb.level || ''))
                    && Number(pair.qa.preference_evidence_strength ?? 0) !== Number(pair.qb.preference_evidence_strength ?? 0)));
    }

    function render(){
        const a = candidates[String(compareA.value)];
        const b = candidates[String(compareB.value)];
        const resultsPanel = document.getElementById('comparisonResults');
        if(!a || !b || String(a.id) === String(b.id)){
            resultsPanel.classList.add('d-none');
            body.innerHTML = '';
            summary.innerHTML = '';
            warning.classList.toggle('d-none', !a || !b);
            return;
        }
        resultsPanel.classList.remove('d-none');
        warning.classList.add('d-none');
        document.getElementById('compareHeaderA').innerHTML = `<div class="fw-bold text-dark text-capitalize">${esc(a.name)}</div><div class="fw-normal text-muted text-lowercase">${esc(a.reference)} · ${esc(a.fit)} · overall evidence quality ${a.reliability ?? 0}%</div>`;
        document.getElementById('compareHeaderB').innerHTML = `<div class="fw-bold text-dark text-capitalize">${esc(b.name)}</div><div class="fw-normal text-muted text-lowercase">${esc(b.reference)} · ${esc(b.fit)} · overall evidence quality ${b.reliability ?? 0}%</div>`;

        let html = '';
        const overallMetricA = {score:a.overall,coverage:a.reliability,purpose:'overall',primary:a.fit,secondary:`Overall Evidence Quality ${a.reliability ?? 0}% · applicant-declared evidence; HR verification is separate`,matched:null,facts:[]};
        const overallMetricB = {score:b.overall,coverage:b.reliability,purpose:'overall',primary:b.fit,secondary:`Overall Evidence Quality ${b.reliability ?? 0}% · applicant-declared evidence; HR verification is separate`,matched:null,facts:[]};
        const [overallRelationA, overallRelationB, overallDelta] = relationPair(overallMetricA, overallMetricB);
        html += `<tr><td><div class="compare-metric-name">Overall Role Fit</div><div class="compare-metric-note">Final evidence-based score</div></td>${cell(overallMetricA, overallRelationA, overallDelta)}${cell(overallMetricB, overallRelationB, overallDelta)}</tr>`;

        const differences = [];
        const tiedEvidence = [];
        metricOrder.forEach(key => {
            const ma = a.metrics[key], mb = b.metrics[key];
            const [relationA, relationB, delta] = relationPair(ma, mb);
            if(delta > 0 && ma.purpose === 'fit'){
                differences.push({key,label:metricLabels[key][0],delta,winner:Number(ma.score)>Number(mb.score)?a:b,loser:Number(ma.score)>Number(mb.score)?b:a,winMetric:Number(ma.score)>Number(mb.score)?ma:mb,loseMetric:Number(ma.score)>Number(mb.score)?mb:ma,high:Math.max(Number(ma.score),Number(mb.score)),low:Math.min(Number(ma.score),Number(mb.score))});
            } else if(ma.purpose === 'fit' && ma.score !== null && mb.score !== null && Number(ma.score) === Number(mb.score) && text(ma.primary, '') !== text(mb.primary, '')){
                tiedEvidence.push({key,label:metricLabels[key][0],ma,mb});
            }
            html += `<tr><td><div class="compare-metric-name">${esc(metricLabels[key][0])}</div><div class="compare-metric-note">${esc(metricLabels[key][1])}</div></td>${cell(ma,relationA,delta)}${cell(mb,relationB,delta)}</tr>`;
            if(key === 'vacancy_qualification_match') html += qualificationBreakdown(ma, mb);
        });
        body.innerHTML = html;

        const lines = [];
        const qaSummary = a.metrics.vacancy_qualification_match || {};
        const qbSummary = b.metrics.vacancy_qualification_match || {};
        const aRequiredFailed = Number(qaSummary.required_not_matched || 0);
        const bRequiredFailed = Number(qbSummary.required_not_matched || 0);
        const aRequiredPending = Number(qaSummary.required_not_verified || 0);
        const bRequiredPending = Number(qbSummary.required_not_verified || 0);

        if(aRequiredFailed !== bRequiredFailed){
            const safer = aRequiredFailed < bRequiredFailed ? a : b;
            const other = aRequiredFailed < bRequiredFailed ? b : a;
            const saferFailed = Math.min(aRequiredFailed, bRequiredFailed);
            const otherFailed = Math.max(aRequiredFailed, bRequiredFailed);
            lines.push(`<div class="summary-line"><b>Required qualification status:</b> ${esc(safer.name)} has fewer confirmed required gaps (${saferFailed} vs ${otherFailed}). This takes priority over a raw score advantage; ${esc(other.name)} requires HR review of the confirmed gap(s).</div>`);
        } else if(aRequiredPending !== bRequiredPending){
            const clearer = aRequiredPending < bRequiredPending ? a : b;
            const pending = aRequiredPending < bRequiredPending ? b : a;
            lines.push(`<div class="summary-line"><b>Verification burden:</b> ${esc(clearer.name)} has fewer required qualifications still awaiting evidence (${Math.min(aRequiredPending,bRequiredPending)} vs ${Math.max(aRequiredPending,bRequiredPending)}). Verify ${esc(pending.name)}'s pending requirement(s) before relying on the score difference.</div>`);
        }

        if(a.overall !== b.overall){
            const winner = Number(a.overall) > Number(b.overall) ? a : b;
            const loser = Number(a.overall) > Number(b.overall) ? b : a;
            const lead = Math.abs(Number(a.overall) - Number(b.overall));
            lines.push(`<div class="summary-line"><b>Role Fit score:</b> ${esc(winner.name)} has the higher evidence-based score by ${lead.toFixed(1)} point(s), ${scoreText(winner.overall)} vs ${scoreText(loser.overall)}. Fit labels: ${esc(winner.fit)} vs ${esc(loser.fit)}; overall evidence quality ${winner.reliability ?? 0}% vs ${loser.reliability ?? 0}%. These percentages measure evidence completeness/specificity, not truth verification. Required-gap status above still takes precedence.</div>`);
        }

        const qDiffs = qualificationDifferences(a.metrics.vacancy_qualification_match, b.metrics.vacancy_qualification_match);
        qDiffs.slice(0,2).forEach(({qa,qb}) => {
            const preferredA = qa.preferred_bonus ? `; preferred advantage ${qa.preferred_bonus_matched === true ? 'evidenced' : 'not evidenced'}` : '';
            const preferredB = qb.preferred_bonus ? `; preferred advantage ${qb.preferred_bonus_matched === true ? 'evidenced' : 'not evidenced'}` : '';
            const optionalA = ['preferred','nice_to_have'].includes(String(qa.level || '')) ? `; optional evidence ${qa.preference_evidence_summary || 'n/a'}` : '';
            const optionalB = ['preferred','nice_to_have'].includes(String(qb.level || '')) ? `; optional evidence ${qb.preference_evidence_summary || 'n/a'}` : '';
            lines.push(`<div class="summary-line"><b>${esc(short(qa.qualification, 90))}:</b> ${esc(a.name)} — ${statusLabel(qa.status)} (${esc(short(qa.evidence, 105))}${esc(preferredA)}${esc(optionalA)}); ${esc(b.name)} — ${statusLabel(qb.status)} (${esc(short(qb.evidence, 105))}${esc(preferredB)}${esc(optionalB)}).</div>`);
        });

        const prefStrengthA = Number(qaSummary.preference_evidence_strength ?? 0);
        const prefStrengthB = Number(qbSummary.preference_evidence_strength ?? 0);
        if(Number(qaSummary.preferred_total || 0) > 0 && Number(qbSummary.preferred_total || 0) > 0 && prefStrengthA !== prefStrengthB){
            const prefWinner = prefStrengthA > prefStrengthB ? a : b;
            const prefLoser = prefStrengthA > prefStrengthB ? b : a;
            const preferredSummary = candidate => {
                const rows = Array.isArray(candidate.metrics?.vacancy_qualification_match?.qualification_details)
                    ? candidate.metrics.vacancy_qualification_match.qualification_details
                    : [];
                const summaries = rows
                    .filter(row => ['preferred','nice_to_have'].includes(String(row.level || '')))
                    .map(row => text(row.preference_evidence_summary, ''))
                    .filter(Boolean);
                return summaries.length ? summaries.join('; ') : 'No optional advantage evidenced';
            };
            lines.push(`<div class="summary-line"><b>Optional/preferred evidence:</b> ${esc(prefWinner.name)} has broader directly evidenced optional coverage. ${esc(a.name)}: ${esc(preferredSummary(a))}; ${esc(b.name)}: ${esc(preferredSummary(b))}. This is a tie-breaker only and does not change either candidate's required-qualification or Role Fit score.</div>`);
        }

        differences.filter(item => item.key !== 'vacancy_qualification_match').sort((x,y)=>y.delta-x.delta).slice(0,2).forEach(item => {
            lines.push(`<div class="summary-line"><b>${esc(item.label)}:</b> ${esc(item.winner.name)} leads ${item.delta.toFixed(1)} point(s), ${item.high.toFixed(1)}% vs ${item.low.toFixed(1)}%. Evidence: ${esc(item.winner.name)} — ${esc(text(item.winMetric.primary))}; ${esc(item.loser.name)} — ${esc(text(item.loseMetric.primary))}.</div>`);
        });

        tiedEvidence.slice(0,1).forEach(item => {
            lines.push(`<div class="summary-line"><b>${esc(item.label)}:</b> same score at ${scoreText(item.ma.score)}, but the evidence differs — ${esc(a.name)}: ${esc(text(item.ma.primary))}; ${esc(b.name)}: ${esc(text(item.mb.primary))}.</div>`);
        });

        if(Math.min(Number(a.reliability ?? 0), Number(b.reliability ?? 0)) < 65){
            lines.push('<div class="summary-line"><b>Evidence caution:</b> at least one candidate has low Overall Evidence Quality. Verify missing requirements and applicant claims before relying on the ranking.</div>');
        }
        if(!lines.length){
            lines.push('<div class="summary-line">The selected candidates have nearly identical scored evidence. Review the qualification-by-qualification matrix and exact supporting facts above before interview.</div>');
        }
        summary.innerHTML = lines.join('');
    }

    document.getElementById('runComparison')?.addEventListener('click', render);
    compareA?.addEventListener('change', render);
    compareB?.addEventListener('change', render);
    render();
})();
</script>
@endif
<script>
(function(){
    const hash = window.location.hash;
    if(hash){
        const button = document.querySelector(`[data-bs-target="${hash}"]`);
        if(button) bootstrap.Tab.getOrCreateInstance(button).show();
    }
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(button => {
        button.addEventListener('shown.bs.tab', event => history.replaceState(null, '', event.target.dataset.bsTarget));
    });
})();
</script>
@endpush
