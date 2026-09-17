# Assessment Insights Calibration V8

## Goal
V8 focuses on calibration and explainability after the V7 reliability fixes. It keeps the fixed, automatic HR workflow while making the displayed evidence scores less misleading and the Problem Solving / qualification logic more faithful to the evidence actually submitted.

## Changes

### 1. Result/outcome detection is more accurate
The structured Situation–Action–Result scorer now recognizes natural outcome language such as:
- restored / returned to normal / resumed / recovered
- able to continue work / without escalation
- did not recur / no recurrence / remained stable
- Filipino/Taglish equivalents such as `hindi na naulit`, `bumalik sa normal`, `nakapagpatuloy`, and related phrases

The comparison no longer shows `0 result signals` for strong outcomes like the seeded Marco and Andrea examples.

### 2. Product/version numbers no longer inflate evidence specificity
Bare software numbers such as `Windows 11`, `Microsoft 365`, or `Windows Server 2019` are no longer counted as measurable evidence.

Quantitative evidence is counted only when a number is tied to a meaningful unit, for example:
- `20 users`
- `2 hours`
- `15 tickets`
- `10%`

### 3. Problem Solving scoring is conservative near the top end
A detailed qualitative result can still score highly, but near-perfect Problem Solving scores require a bounded/measurable outcome. This prevents well-written self-reports from appearing more certain than the submitted evidence supports.

### 4. Assessment Reliability was renamed to Evidence Quality in the UI
The legacy database column remains `confidence` for compatibility, but the UI now calls it **Evidence Quality**.

Evidence Quality means:
- evidence completeness,
- specificity,
- assessable coverage,
- fixed-form readiness.

It does **not** mean that the applicant's claims have been independently verified and it is not a probability of job success.

The UI now explicitly states: `Applicant-declared; HR verification separate`.

### 5. Evidence Quality is recalibrated
Full form coverage can no longer push moderately specific answers to 99–100% by itself. The overall Evidence Quality indicator is capped close to the underlying role-evidence specificity score.

This keeps complete but generic answers from looking nearly perfect.

### 6. Preferred / nice-to-have qualifications no longer penalize hard-requirement fit
When a vacancy has required qualifications:
- Required qualifications form the 45% qualification baseline.
- Preferred / nice-to-have items are shown separately.
- Missing an optional preference does not reduce the hard-requirement score.
- Preferred matches are used as optional advantages / ranking tie-breakers after required-gap status, fit label, and Role Fit score.

If a vacancy contains no required qualifications, the available qualification set remains assessable as the baseline.

### 7. Comparison is clearer
Head-to-head comparison now distinguishes:
- required-baseline evidence coverage,
- all-qualification evidence coverage,
- required matches,
- preferred / nice-to-have evidence,
- Evidence Quality vs Role Fit,
- applicant-declared evidence vs HR verification.

The circular candidate percentage is also explicitly labeled **Role Fit**.

## Model Version
`AssessmentInsightService::MODEL_VERSION = 8`

Existing V7 results are automatically recalculated when Assessment Insights is opened or when HR uses **Refresh Scores**.

## Database changes
V8 does not add a new database schema migration. If the project is already on V7, only clear application caches after replacing the files:

```bash
php artisan optimize:clear
```

If upgrading from a version older than V7, run the previously included migrations first:

```bash
php artisan migrate
php artisan optimize:clear
```

## Validation completed
- Assessment V6 compatibility checks: 18/18 passed
- Assessment V7 reliability checks: 14/14 passed
- Assessment V8 calibration checks: 21/21 passed
- Vacancy status/deadline regression checks: 3/3 passed
- 81 PHP files passed syntax validation
- Assessment Insights Blade template compiled to valid PHP
- Comparison JavaScript passed `node --check`

Full PHPUnit cannot run in the provided execution environment because the PHP `dom`, `mbstring`, and `xmlwriter` extensions are unavailable. The included PHPUnit tests were updated with V8 regression cases for natural outcome detection, version-number specificity, and optional qualification handling.
