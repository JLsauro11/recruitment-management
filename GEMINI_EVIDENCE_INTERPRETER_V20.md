# Gemini Evidence Interpreter V20

## Language-neutral evidence polish
- Gemini remains an evidence interpreter only; it does not score, rank, recommend, hire, or reject.
- Added explicit English / Tagalog / Taglish semantic-equivalence instruction.
- Added prompt-injection resistance: applicant answers are treated as untrusted evidence/data only.
- Added structured evidence status (`explicit`, `semantically_supported`, `ambiguous`, `not_evidenced`) and basis (`direct`, `semantic`, `ambiguous`, `none`).
- Added grounded evidence excerpt to the interpreter trace payload.
- Added language(s), canonical evidence count, and deterministic scoring indicator to the visible Interpreter Trace.
- Interpreter cache version bumped to V7 so old Gemini normalization cache is not reused.

## After copying
Run:

```bash
php artisan optimize:clear
```

Then open Assessment Insights and click **Refresh Scores**.

Validation target: applicants containing equivalent facts in English, Tagalog, and Taglish should produce materially equivalent canonical evidence. Exact final percentages do not need to be identical when the submitted evidence differs in specificity.
