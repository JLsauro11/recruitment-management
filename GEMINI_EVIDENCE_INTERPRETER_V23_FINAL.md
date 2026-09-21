# Gemini Evidence Interpreter V23 - Final Language-Neutral Scoring Pass

## Goal
Keep Gemini as a semantic interpreter only, while all qualification statuses, scoring, fit labels, and ranking stay deterministic and auditable in Laravel.

## Final fixes
- Canonical Gemini competencies are now the primary input for Role-Specific Skills scoring when available.
- English / Tagalog / Taglish wording no longer earns extra points from synonym count or sentence verbosity.
- `hardware and software support` is mapped to the same required competency family as `hardware/software support`.
- System administration remains conservative: the label alone cannot create evidence without a concrete administration operation.
- Structured Problem Solving uses semantic evidence buckets instead of raw translated word/verb counts.
- Role Evidence Quality uses semantic category presence rather than rewarding verbose translations.
- Mixed-case Gemini canonical labels are normalized safely.
- Old AI cache entries are invalidated by the interpreter version bump.
- Assessment model version is bumped so refreshed scores use the new scoring rules.
- `.env.example` now documents Gemini only and no longer contains stale OpenAI configuration comments.

## Expected parity behavior
For applicants who state the same underlying facts in English, Tagalog, or Taglish:
- Vacancy Qualification Match should be the same.
- Relevant Experience should be the same.
- Required Role-Specific competency set should be the same.
- Problem Solving should fall into the same semantic score bucket.
- Role Fit should no longer move simply because of translation verbosity or synonym choice.

Small differences are still legitimate when the actual evidence differs, such as an extra named tool, measurable result, missing action/result, or a genuinely different competency.

## QA commands used
```bash
php tests/manual_ai_language_invariance_v21.php
php tests/manual_ai_language_invariance_v22.php
php tests/manual_language_neutral_scoring_v23.php
node tests/assessment-comparison-ui.cjs
find app database routes tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Local refresh after installing this project
```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan db:seed --class=GeminiLanguageAssessmentTestSeeder
```
Then open Assessment Insights for **IT SPECIALIST - GEMINI LANGUAGE TEST** and click **Refresh Scores**.
