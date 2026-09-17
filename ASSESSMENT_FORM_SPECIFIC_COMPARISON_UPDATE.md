# Assessment + Form Specific Comparison Update

## Changes
- Employment History now collects start date, end date, current-employment flag, reason for leaving, and key duties/achievements per employer.
- References are grouped as separate professional-reference records in both the applicant form and print/PDF layout.
- Questionnaire includes a reusable Role-Specific Assessment section that works for every current/future vacancy.
- Applicant submission now saves only the form templates assigned to the selected vacancy (with the existing all-active fallback when no assignment exists).
- Assessment scoring uses structured role-aware evidence: experience duration/relevance, role-specific skills, concrete problem solving, questionnaire specificity, education/certifications, and availability/notice period.
- Candidate Comparison tab shows the exact evidence behind each criterion score and explains the strongest weighted differences between #1 and #2.
- Existing applicants keep their old answers. New fields are added non-destructively; old applications can show N/A for evidence they never answered.
- Printable forms are paginated by logical sections; long questionnaire answers are no longer forced into tiny multi-column cards.

## After replacing the project
Run:

```bash
php artisan migrate
php artisan optimize:clear
```

Do not use `migrate:fresh` on a live database.
