# Assessment Insights V14 - Hybrid AI Evidence Interpreter

## What changed

Assessment Insights now supports an optional AI semantic interpretation layer for English, Tagalog, and Taglish applicant evidence.

The AI **does not score applicants**. It only creates a conservative English normalization of applicant-provided evidence. The existing `AssessmentInsightService` still performs all deterministic scoring, weighting, qualification matching, score caps, fit labels, and comparison logic.

Flow:

`Applicant answers -> optional AI semantic normalization -> existing deterministic scoring engine -> Assessment Insights`

If the API key is missing, the AI is disabled automatically and the existing rule/regex interpreter is used. If an API request fails, the assessment also falls back automatically instead of failing.

## Files added/updated

- `app/Services/AiEvidenceInterpreter.php`
- `config/recruitment_ai.php`
- `app/Services/AssessmentInsightService.php` (model version 14)
- `.env.example`

No Composer package is required; Laravel's built-in HTTP client is used.

## Local setup later

Add these values to `.env` when an OpenAI API key is available:

```env
RECRUITMENT_AI_ENABLED=true
OPENAI_API_KEY=
OPENAI_MODEL=gpt-5.6-luna
OPENAI_TIMEOUT=30
RECRUITMENT_AI_CACHE_DAYS=30
RECRUITMENT_AI_MIN_CONFIDENCE=0.80
```

Then clear cached configuration:

```bash
php artisan optimize:clear
```

Without an API key, no further setup is needed and Assessment Insights continues using the existing deterministic interpreter.

## Safety / stability rules

- AI output never directly sets a score.
- AI output never sets Matched / Not Met / Needs Verification / Not Evidenced directly.
- Original answers remain unchanged in the database; high-confidence semantic normalization is used only as an in-memory interpretation copy so text is not double-counted.
- Low-confidence normalizations are ignored.
- Personal identifiers and demographic fields are not sent to the AI interpreter.
- API failures automatically use the existing rule-based fallback.
- AI responses are cached by vacancy + evidence + model to reduce repeated API calls.
