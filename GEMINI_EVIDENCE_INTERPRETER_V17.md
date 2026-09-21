# Gemini Evidence Interpreter V17

V17 fixes Assessment Insights interpreter observability.

## Changes
- Bumps AssessmentInsightService model version to 17 so existing assessments are recomputed.
- Persists interpreter metadata on the first score row using both `interpreter_meta` and legacy `evidence_interpreter`.
- Fixes Candidate Ranking counters so Gemini/fallback counts come from the actual candidate metadata.
- Fixes candidate badges that previously showed `Rule fallback` even when Gemini metadata existed on later score rows.
- Adds candidate-level Interpreter Trace with source, provider, model, HTTP status, cache status, accepted fields, confidence, response model, token count, and exact fallback reason.
- Adds successful Gemini transport diagnostics (HTTP 200, response model version, token counts).
- Bumps AiEvidenceInterpreter cache/schema version to 4.

## After copying the update
Run:

```bash
php artisan optimize:clear
```

Then open Assessment Insights and click **Refresh Scores** once.
