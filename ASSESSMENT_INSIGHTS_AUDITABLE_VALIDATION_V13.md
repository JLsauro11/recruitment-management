# Assessment Insights V13 - Auditable Required Gaps + Cross-Role Validation

## Purpose
V13 makes a required-gap warning directly auditable from Candidate Ranking and adds two different demo vacancies so the assessment engine can be validated outside the IT Specialist sample.

## Candidate Ranking gap traceability
When a candidate has a confirmed required-qualification failure, the red warning is now clickable.

The expanded panel shows:
- the exact required qualification that failed;
- why the submitted evidence does not satisfy it;
- the evidence source used by the assessment; and
- a button to open the full assessment insight.

Required items that are merely missing/ambiguous remain separate under a yellow **pending verification** panel. They are not mislabeled as confirmed failures.

## Reliability fixes found through cross-role testing
Cross-role regression data exposed two edge cases that V13 corrects:

1. Explicit negative experience statements such as **"I do not yet have bank-reconciliation experience"** now take precedence over repeated keywords. The candidate can no longer receive a false positive merely because the missing skill was mentioned while describing a training gap.
2. Concrete business problem-solving verbs such as **traced, reconciled, corrected, verified, compared, checked, reviewed, found, and isolated** are recognized as behavioral problem-solving evidence. This improves non-IT assessment without weakening the existing IT rules.

The assessment model is version 13, so older stored assessment results are automatically recalculated when Assessment Insights is opened.

## Added validation vacancies
The local/testing seeder creates two additional vacancies with three applicants each:

### ACCOUNTING ASSOCIATE - ASSESSMENT VALIDATION
- Camille Joy Reyes - strong evidence profile
- Brian Paul Mendoza - moderate profile with one required item needing verification
- Nina Rose Villanueva - multiple confirmed required gaps

Expected evidence-based ordering: **Camille > Brian > Nina**.

### INVENTORY CONTROL OFFICER - ASSESSMENT VALIDATION
- Jerome Allan Navarro - strong inventory-control profile
- Hannah Grace Flores - good/adjacent warehouse profile
- Kevin James Ramos - entry-level profile with confirmed required gaps

Expected evidence-based ordering: **Jerome > Hannah > Kevin**.

These samples intentionally use different degree, experience, behavioral, operational, and tool evidence so the engine is not tested only against IT terminology.

## Seeding
For an existing local development database:

```bash
php artisan db:seed --class=AssessmentValidationDemoSeeder
php artisan optimize:clear
```

Or run the normal local seeder:

```bash
php artisan db:seed
php artisan optimize:clear
```

`DatabaseSeeder` includes the validation demo only when `APP_ENV` is `local` or `testing`, so production databases are not automatically populated with demo applicants.

## Validation performed
- V6 regression checks: passed
- V7 reliability checks: passed
- V8 calibration checks: passed
- V9 final-calibration checks: passed
- V12 production-readiness checks: passed
- V13 cross-role + required-gap traceability checks: passed (18/18)
- Vacancy deadline/status checks: passed
- Assessment comparison JavaScript checks: passed (13 assertions)

The demo data is applicant-declared evidence for testing. As in production, Assessment Insights remains decision support; document/reference/interview verification stays with HR.
