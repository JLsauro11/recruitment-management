# Assessment Insights + Simplified Application V6

## Goal
Reduce applicant effort while keeping Assessment Insights evidence-based. V6 removes repeated/low-value public-form questions and moves detailed competency evidence into one fixed Role-Specific Assessment.

## Public Application changes

### Job Application Data
Only the basic fields remain:
- Availability to Start
- Current Employment Status
- Notice Period — shown/required only when the applicant is currently working
- Applied Through
- Referred By — shown/required only for Employee Referral

Removed from the public workflow: specific start-date, shift/schedule readiness, and on-site readiness questions.

### Personal Information
- One Address field only
- Facebook Account removed
- Age remains auto-calculated from Birthdate and is never used in Assessment Insights

### Educational Background
Only school history remains:
- Elementary
- High School
- Vocational School / Course
- College / University, College Address, Degree / Course, College Inclusive Dates

Removed from the public workflow:
- Degree completion status
- Graduation/expected graduation year
- Professional qualification/license records
- Certification/training records

`Degree / Course` is retained because it is the only concise structured evidence needed when a vacancy explicitly contains an education requirement. Degree claims remain subject to HR/document verification.

### Employment History
- A Yes/No work-experience declaration appears first.
- If Yes, only Employment Record 1 is shown initially.
- `+ Add Another Employment Record` adds another record only when needed, up to 5.
- Active records require company name, company address, position, start date, and current-employment status.
- End date is required for previous employment and disabled for a current employer.
- Any record after #1 can be removed; later records shift upward so there are no gaps.

Removed from every employment record:
- Reason for Leaving
- Primary Responsibilities
- Tools / Software / Systems Used
- Key Achievements / Results

These details are no longer repeated per employer. Detailed skills, actual tools/platforms, comparable work, and Situation–Action–Result evidence are collected once in the fixed Employment Questionnaire.

## Assessment Insights V6 changes
- Assessment model version: **6**
- Vacancy Qualification Match remains **45%** of final Role Fit.
- Supporting Role Fit is still **55%**, internally weighted as:
  - Relevant Experience: **30%**
  - Role-Specific Skills: **45%**
  - Problem Solving & Results: **25%**
- Employment experience uses position titles + valid dates only and supports up to 5 employers.
- Role-Specific Skills ignores removed legacy employment-detail fields so old duplicated text cannot inflate a new score.
- Tool matching now includes common IT-support technologies such as Windows, Windows Server, Active Directory, Microsoft 365, DNS, DHCP, Remote Desktop, AnyDesk, and TeamViewer.
- Education is context-only except when an explicit vacancy education requirement exists. It is scored once under Vacancy Qualification Match.
- Since completion-status is no longer asked, explicit degree-level wording such as `Bachelor of Science in Information Technology` can support a degree requirement, while a generic course label such as `Information Technology` stays **Needs Verification**.
- Removed readiness questions such as on-site/shift willingness are never guessed from unrelated fields. If a vacancy still requires them, Assessment Insights marks them **Not Evidenced** and tells HR to verify directly.
- Certifications/licenses are no longer requested in the concise public form. New applicants therefore receive **Not Evidenced / HR verification required** for an explicit credential requirement unless legacy structured evidence exists.
- Evidence Quality remains reliability-only and does not add Role Fit points.

## IT Specialist demo data
`database/seeders/ItSpecialistSampleApplicantsSeeder.php` creates a dedicated vacancy:

**IT SPECIALIST - ASSESSMENT DEMO**

with 5 representative IT qualifications and three different sample applicants:
1. Marco Dela Cruz — stronger IT support/system administration evidence
2. Andrea Santos — moderate help desk/support evidence with infrastructure training gaps
3. Paolo Reyes — junior/entry-level hardware support background with larger system-administration gaps

The seeder submits both official forms and automatically calculates Assessment Insights for each sample application.

## Update an existing V5 database
Run:

```bash
php artisan migrate
php artisan optimize:clear
```

Then seed only the IT Specialist demo applicants:

```bash
php artisan db:seed --class=ItSpecialistSampleApplicantsSeeder
```

Or in local/testing environments, normal `php artisan db:seed` also includes the demo seeder after the core seeders.

## Data safety
The V6 migration does **not delete** V5 form-field rows because historical `form_answers` reference those IDs. Legacy fields are made non-required and moved out of the active workflow; the V6 fixed-form allow-list hides them from new applicants.

## Validation performed for this delivery
- PHP syntax validation for modified application, assessment, migration, and seeder files
- Assessment V6 manual regression suite: **18/18 passed**
- Blade compilation is checked directly with Laravel's Blade compiler because this execution environment does not have PHP `dom`, and it has no PDO database driver for an end-to-end migration/seeder database run.
