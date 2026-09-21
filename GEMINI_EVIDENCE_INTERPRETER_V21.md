# Gemini Evidence Interpreter V21 - Language-Invariant Canonicalization

This pass keeps Gemini as an evidence interpreter only. The deterministic Assessment Insight engine still owns qualification matching, scoring, fit labels, ranking, and comparison.

## What changed

- Interpreter version bumped to **8** so old cached normalizations are automatically invalidated.
- Assessment model version bumped to **20** so refreshed results are clearly versioned.
- Added a post-Gemini canonical competency normalizer.
- Equivalent English, Tagalog, and Taglish evidence now collapses to the same stable competency families whenever the underlying facts are the same.
- Overlapping model labels such as `hardware support`, `software support`, and `hardware and software support` are deduplicated into one family.
- `help desk` / `technical support` normalize to `IT support`; `problem solving` normalizes to `troubleshooting` for competency matching.
- Deterministic backfill is performed only from Gemini's normalized English output and only when evidence is `explicit` or `semantically_supported` and meets the configured confidence threshold.
- Ambiguous or low-confidence model output cannot create positive canonical evidence.
- Semantic trace now keeps both the raw Gemini competency list and the final canonical list for audit/debugging.

## Why

Gemini can legitimately paraphrase equivalent multilingual answers differently. Without a stable post-processing ontology, a translated answer could accidentally produce 5 competency labels while another wording produces 6. V21 removes that wording/language advantage before the existing rules engine sees the evidence.

## Local regression check

```bash
php tests/manual_ai_language_invariance_v21.php
```

Expected result: all language-invariance checks pass.

## After updating localhost

```bash
php artisan optimize:clear
php artisan cache:clear
php artisan db:seed --class=GeminiLanguageAssessmentTestSeeder
```

Then open **IT SPECIALIST - GEMINI LANGUAGE TEST** and click **Refresh Scores**. Check the Strong English / Strong Tagalog / Strong Taglish triplet, followed by the Weak and Tool Name Drop triplets.
