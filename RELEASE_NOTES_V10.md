# Recruitment Management — V10 reliability update

Updated from the supplied recruitment-management-assessment-final-v9(1).zip.

## Assessment corrections

- Model version 10 invalidates V9 scores on opening Assessment Insights.
- Input fingerprints detect changed answers, vacancy requirements, position names and calendar dates. Current employment and availability are refreshed as dates advance. Evidence is eager-loaded for the selected vacancy.
- Only Application for Employment and Employment Questionnaire submissions supply scoring keys. When repeated submissions exist, newer submissions take precedence deterministically.
- Missing/invalid employment dates produce N/A and zero assessable coverage, rather than an invented 35-point score.
- Impossible calendar dates, relative date prose and future employment intervals are excluded. Existing ISO year-month employment data remains accepted as the first day of that month; full ISO dates are required for new/edited date fields.
- Partial employment history below a required duration needs verification instead of being presented as a confirmed failure. Invalid records cannot improve title-alignment scores.
- Overlapping jobs are merged; fractional months across separate jobs are accumulated before rounding down once.
- Experience ranges such as 2–3 years use the lower bound. Preferred experience minimums do not change the supporting experience baseline.
- Explicit undergraduate/incomplete wording in Degree / Course is respected. An associate degree cannot satisfy an explicit bachelor's requirement.
- Optional-only vacancy qualifications do not create a required-baseline score or a normal fit label; readiness also requires a required qualification.
- Detailed candidate cards show the actual weighted calculation, any required-gap cap, missing supporting weights and assessment timestamp.
- Scores and differences retain one decimal place. Missing evidence is not called a tie. Comparison remains hidden until two different candidates are selected, and invalid selections clear old results.

## Forms and other corrections

- Public step validation uses the same visible field set as final submission, avoiding legacy hidden-field requirements.
- A shared validator enforces employment completeness/chronology on public submissions and staff edits. Date fields with matching start/end or from_date/to_date keys also receive chronology validation. This is naming-based; arbitrary semantic field relationships still need an explicit rule.
- Staff edits preserve omitted non-checkbox values. Unchecked checkbox groups are still cleared. Future dates remain allowed for general date fields; birth and employment dates cannot be in the future.
- A failed reassessment after an edit removes the stale assessment and returns a pending-refresh message; opening Assessment Insights retries.
- Monthly dashboard trends use month boundaries, avoiding end-of-month overflow. Active interview counts include Rescheduled records.
- Interview synchronization cannot silently reopen Hired, Rejected or Withdrawn decisions.
- CSV exports protect text cells from spreadsheet formula interpretation and include a UTF-8 BOM for Excel.
- PHPUnit is isolated to an in-memory SQLite database. The guest-root test now checks the actual careers redirect.

## Validation completed here

- PHP syntax parser: **99 application/configuration/migration/route/test files parsed; zero syntax errors**. This is a parser check, not PHP execution.
- Actual comparison JavaScript executed with a minimal DOM fixture: **11 assertions passed**, including decimal differences, missing values, escaped applicant names, empty selection and stale-result clearing. Both inline scripts parsed.
- Added **12 PHP assessment regression tests** and **3 shared-validation tests**. Existing reliability tests remain included.
- Uploaded database/application documents were not modified. No production system was accessed or deployed.

## Validation limits

PHP/Composer are unavailable in this execution environment, so PHP/Laravel tests, migrations, database-backed flows, Blade compilation and browser layout verification could not be executed. The PHP regression tests are included but are **not claimed to pass**. Existing earlier manual scripts reflect their historical model versions and may contain intentionally superseded expectations.

The matching engine is a deterministic heuristic over applicant-declared text and dates. It does not read/verify resumes, authenticate certificates, independently establish graduation, or predict job performance. Ambiguous wording and roles outside its vocabulary still need HR review. No claim of “perfect” or 100% data accuracy is made.

## Update your existing installation

1. Back up your project and database.
2. Apply the updated source while keeping your existing `.env`, database and applicant uploads. Do not overwrite live credentials/settings with a ZIP copy.
3. Run `php artisan optimize:clear`.
4. If migrations from previous versions are pending, run `php artisan migrate`. V10 adds no migration. **Do not run migrate:fresh on an existing database.**
5. Run the checks below using PHP 8.2+ with mbstring, XML and SQLite support:

```text
php artisan test --filter="AssessmentInsightServiceReliabilityTest|AssessmentV10RegressionTest|ApplicationEvidenceValidationTest"
node tests/assessment-comparison-ui.cjs
php artisan view:cache
php artisan view:clear
```

6. Open Assessment Insights for each vacancy; V10 recalculates outdated results automatically. Recalculate remains available for an explicit refresh.
7. In a local/staging copy, submit a sample application, edit its dates, compare two candidates, change a requirement, and confirm the evidence/score/timestamp update before replacing a live deployment.

No npm rebuild is required for these changes: the UI changes are in Blade/inline JavaScript. Keep the existing compiled assets.

## V11 optional-evidence usability calibration

- Model version bumped to 11.
- Optional technology/component breadth now uses literal coverage (for example, 1/4 = 25%, 4/4 = 100%) instead of the earlier synthetic 70+ depth scale.
- The comparison UI shows exact optional-evidence counts/summaries rather than a potentially misleading depth percentage.
- Unverified optional evidence does not receive a tie-break ranking advantage until sufficiently evidenced.
- Required qualification and Role Fit formulas are unchanged from V10.

## V12 production-readiness calibration

- Model version bumped to 12; older assessment results refresh automatically.
- Problem Solving role-context matching now recognizes semantic required-role evidence instead of exact token overlap only.
- Context concepts are deduplicated and optional/preferred tools cannot leak into the Problem Solving Role Fit factor.
- Comparison shows the actual detected role-context concepts for auditability.
- Vacancy qualification summaries display required counts separately from preferred/nice-to-have evidence; the misleading combined `5/5 matched` hard-requirement-looking line is removed when a required baseline exists.
- Missing optional evidence remains a non-scoring tie-breaker and cannot appear as a required qualification gap.
- New V12 direct regression suite and expanded comparison-renderer checks are included.

## Assessment Insights V13 - Auditable Gap + Cross-Role Validation
- Confirmed required-gap count in Candidate Ranking is now clickable and shows the exact failed requirement, explanation, and evidence source.
- Pending verification remains separate from confirmed failure.
- Explicit negative experience statements no longer create false-positive skill matches just because the missing skill is mentioned.
- Broader business problem-solving actions are recognized for non-IT roles.
- Added local/testing validation vacancies for Accounting Associate and Inventory Control Officer, with three distinct applicants each.
- Assessment model version: 13.
