# Assessment Insights - Final Update

Added a complete role-based Assessment Insights module for Admin and HR.

## Included
- Candidate ranking per job vacancy
- Premium RS8 Talent Intelligence dashboard
- Overall role-fit score and fit labels
- Six weighted assessment categories (editable per vacancy)
- Confidence indicator based on available evidence
- Strengths, evidence gaps, interview focus, and assessment summary
- One-click recalculation after applicant/form changes
- Criteria editor with enforced 100% total weighting
- Demographic/protected profile fields excluded from scoring
- HR remains the decision maker; the module does not auto-hire or auto-reject

## After replacing the project
Run: `php artisan migrate`

Do not use `migrate:fresh` on a live/existing database because it deletes existing data.
