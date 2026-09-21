# RS8 Recruitment Management System - Final V25

## Purpose
V25 closes the last language-parity issue observed in the live Assessment Insights comparison between the strong English and strong Taglish fixtures.

## Final scoring hardening
- Role-Specific Skills no longer awards a second numeric bonus for an already-matched competency merely because Gemini repeats that competency in an applied/project evidence field.
- Applied/project evidence remains visible for audit, but the same competency cannot be double-counted.
- Structured Problem Solving no longer changes score because one equivalent Gemini paraphrase contains more raw action verbs or an extra non-measurable outcome qualifier.
- A clear, detailed result with an outcome signal receives the same semantic result bucket across equivalent English, Tagalog and Taglish wording.
- Role Evidence Quality de-duplicates canonical evidence concepts assessment-wide instead of summing the same concept repeatedly across multiple questionnaire fields.
- Gemini interpreter cache version bumped to 11.
- Assessment scoring model version bumped to 22, forcing stale stored assessments to refresh under V25 rules.

## Invariant
Equivalent facts -> equivalent canonical scored evidence -> equivalent deterministic score.
Language, verbosity, synonym count and Gemini paraphrase style must not independently add Role Fit points.

## HR safety boundary
Gemini remains an evidence interpreter only. It does not assign hiring decisions. Deterministic rules calculate Assessment Insights and HR separately verifies applicant-declared evidence.

## After installing
Run:

```bash
composer install
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan db:seed --class=GeminiLanguageAssessmentTestSeeder
```

Then open Assessment Insights for the Gemini language-test vacancy and click **Refresh Scores**.
