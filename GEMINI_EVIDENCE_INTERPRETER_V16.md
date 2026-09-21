# Gemini Evidence Interpreter V16

## What changed

1. Updated Gemini 3.x structured-output request to use `generationConfig.responseFormat.text` with JSON MIME type and schema.
2. Added exact fallback diagnostics for HTTP 400/401/403/404/429/5xx and non-HTTP interpreter errors.
3. Candidate cards now show the fallback reason and HTTP status instead of only `Rule fallback`.
4. The admin diagnostic banner now shows how many current assessments actually used Gemini vs deterministic fallback.
5. AI interpreter cache version bumped so old failed/legacy-normalization cache entries are not reused.
6. Assessment model version bumped to V16 so stale V15 assessment results are recomputed.

## After copying the update

Run:

```bash
php artisan optimize:clear
```

Then open Assessment Insights and click **Refresh Scores**.

Expected successful candidate badge:

`Gemini AI · XX% semantic confidence`

If Gemini fails, the candidate now shows a reason such as:

- `Gemini rejected the request: ... (HTTP 400)`
- `Gemini API key or project access was rejected: ... (HTTP 403)`
- `Gemini model or endpoint was not found: ... (HTTP 404)`
- `Gemini free-tier/rate limit was reached: ... (HTTP 429)`

## Important

Gemini is still used only as a semantic evidence interpreter. Final qualification status, weights, scoring, fit labels, and ranking remain deterministic in `AssessmentInsightService`.
