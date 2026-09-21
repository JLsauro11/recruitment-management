# Gemini Evidence Interpreter V18

## Fixes
- Fixed Gemini HTTP 400 request compatibility by switching the generateContent structured-output payload to `responseMimeType: application/json` + `responseJsonSchema`.
- Uses canonical REST `systemInstruction` field.
- Fixed the Assessment Insights diagnostic bar so Blade directives are no longer displayed as raw text.
- Assessment model bumped to V18 to force recomputation of existing test assessments.
- AI interpreter cache version bumped so V17 fallback results are not reused.

## After copying
Run:

```bash
php artisan optimize:clear
```

Then open Assessment Insights and click **Refresh Scores**.

Expected successful diagnostic:
- Gemini AI count > 0
- fallback count falls to 0 for valid test records
- Last API status: HTTP 200
- candidate badge: Gemini AI

If a fallback remains, open **Interpreter trace** to inspect the exact error reason.
