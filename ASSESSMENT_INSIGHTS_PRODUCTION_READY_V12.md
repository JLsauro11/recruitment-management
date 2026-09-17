# Assessment Insights V12 — production-readiness calibration

V12 keeps the V10/V11 Role Fit weights and fixes the last explainability/accuracy issue found in the IT Specialist demo comparison.

## Final corrections

- Problem Solving role-context matching is now semantic and requirement-bound instead of relying only on exact word overlap.
- Natural technical evidence such as IP configuration, DHCP, DNS, gateway, connectivity, Windows profile/credentials, documentation, and troubleshooting actions can now map to the required role concepts they actually demonstrate.
- Role-context concepts are deduplicated so `Networking` and `Basic networking`, or a Windows tool mention and Windows-support concept, do not inflate the small role-context factor.
- Only **required** vacancy context can influence the Problem Solving role-context score. Preferred/nice-to-have tools remain tie-breakers and cannot leak into Role Fit.
- Problem Solving comparison now lists the exact required-role concepts detected, making the score auditable by HR.
- Vacancy Qualification comparison no longer collapses required and optional rows into a misleading aggregate such as `5/5 matched`.
- Required qualification counts now display separately: required met / not met / not evidenced / needs verification.
- Preferred/nice-to-have evidence is displayed separately and is explicitly labeled as non-scoring tie-breaker evidence.
- Summary text also uses required-specific gap counts so a missing optional preference cannot look like a required hiring gap.
- Assessment model version is now **12**, so V11 and older results refresh automatically when Assessment Insights is opened or scores are refreshed.

## Role Fit formula unchanged

- Required vacancy qualification baseline: **45%**
- Supporting role evidence: **55%**
  - Relevant Experience: **30% of supporting block**
  - Role-Specific Skills: **45% of supporting block**
  - Problem Solving & Results: **25% of supporting block**

Overall Evidence Quality and Role Evidence Quality remain non-Role-Fit reliability/explainability indicators. Applicant-declared evidence still requires HR interview/document/reference verification.

## Validation completed for V12

- Assessment V7 reliability checks: **14/14 passed**
- Assessment V8 calibration checks: **21/21 passed**
- Assessment V9/V11 calibration checks: **14/14 passed**
- New V12 production-readiness checks: **14/14 passed**
- Vacancy status/deadline checks: **3/3 passed**
- Comparison renderer: **13/13 assertions passed**, both inline scripts parsed
- Blade templates: **28/28 compiled and PHP-linted** through the Blade compiler
- PHP parser: **87 app/route/database/test PHP files** linted with zero syntax errors

The container used for this review does not provide PHP DOM/XML/mbstring extensions required by the normal Artisan/PHPUnit console stack, so the full framework test command was not claimed here. The included deterministic/manual regression tests exercise the assessment service directly with compatibility polyfills.

## Install/update

No new database migration is required from V11.

1. Back up the existing project/database.
2. Replace the project source while preserving the live `.env`, storage uploads, and database.
3. Run:

```bash
php artisan optimize:clear
```

4. Open **Assessment Insights** or click **Refresh Scores**. Model V12 automatically replaces older assessment results.

For a normal development/staging machine with the full PHP extensions installed, also run:

```bash
php artisan test --filter="AssessmentInsightServiceReliabilityTest|AssessmentV10RegressionTest|AssessmentV11OptionalEvidenceTest"
node tests/assessment-comparison-ui.cjs
php tests/manual_assessment_v12_check.php
```

Assessment Insights remains decision support, not an automatic hire/reject engine.
