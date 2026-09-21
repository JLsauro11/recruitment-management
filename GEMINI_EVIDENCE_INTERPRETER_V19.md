# Gemini Evidence Interpreter V19

## Fixes
- Fixed Gemini HTTP 400 structured-output payload for `generateContent`.
- Replaced `generationConfig.responseJsonSchema` with the documented `generationConfig.responseSchema`.
- Kept `responseMimeType: application/json`.
- AI interpreter cache version bumped so previous HTTP 400 fallback results are not reused.
- Assessment model bumped to V19 so existing test assessments recompute.
- Existing candidate-level Interpreter Trace remains available for exact API/fallback diagnostics.

## After copying
Run:

```bash
php artisan optimize:clear
```

Then open Assessment Insights and click **Refresh Scores**.

Expected if Gemini succeeds:
- Gemini AI count greater than 0
- Last API status: HTTP 200
- Candidate badge: Gemini AI

If any candidate still falls back, open **Interpreter trace** and inspect the exact returned Gemini error.
