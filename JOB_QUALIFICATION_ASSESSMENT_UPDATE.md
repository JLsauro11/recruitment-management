# Job Vacancy Qualification Assessment Update

## What changed
- Job Vacancy Qualifications are now the primary assessment baseline.
- Added structured qualification records per vacancy: type, requirement level, minimum value/unit, evidence source, and importance.
- Existing free-text vacancy qualifications are backfilled automatically on migration.
- Assessment Role Fit now uses:
  - 45% Vacancy Qualification Match
  - 55% configured supporting assessment criteria
- Required qualifications have the highest impact; Preferred and Nice-to-Have requirements have lower impact.
- Each qualification is labeled Matched, Not Matched, or Not Verified using job-related applicant evidence only.
- Experience requirements compare verified employment duration against configured minimum years/months.
- Education, skill, certification, and availability requirements use the relevant form evidence.
- Candidate Comparison now includes the exact vacancy qualification evidence per candidate.
- Sensitive demographic fields remain excluded from scoring.

## Deployment
Run:

    php artisan migrate
    php artisan optimize:clear

Do not use `migrate:fresh` on a database containing live recruitment records.
