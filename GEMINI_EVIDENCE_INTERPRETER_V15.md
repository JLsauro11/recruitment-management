# Gemini Evidence Interpreter V15

- Replaced the OpenAI-specific evidence-normalization request with Google Gemini GenerateContent API.
- Default model: `gemini-3.1-flash-lite`.
- Added structured JSON output with canonical competency extraction for language-equivalent English, Tagalog, and Taglish evidence.
- Canonical competency tags are appended only to skill-related fields, never STAR problem/action/result fields, preventing artificial action/result inflation.
- Added admin diagnostics showing configured provider/model and a per-candidate badge showing whether Gemini or rule-based fallback actually produced the current assessment.
- Per-candidate metadata includes semantic confidence, cache status, fallback status, provider, model, and accepted/submitted field counts.
- Scoring formula, qualification logic, caps, and ranking precedence remain deterministic and unchanged.
- Model version bumped to 15 so existing candidates automatically recalculate once after this update.

## .env

```env
RECRUITMENT_AI_ENABLED=true
AI_PROVIDER=gemini
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-3.1-flash-lite
GEMINI_TIMEOUT=30
RECRUITMENT_AI_CACHE_DAYS=30
RECRUITMENT_AI_MIN_CONFIDENCE=0.80
```

After changing `.env`, run `php artisan optimize:clear`, then use **Refresh Scores** in Assessment Insights.
