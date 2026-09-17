# Assessment Insights + Application Evidence V5

## Purpose
This update makes Assessment Insights more conservative, explainable, and easier for HR/Admin to use without manual criteria, form, weight, or question-mapping setup. It also fixes vacancy reopening when an expired closing date is extended.

## 1. Vacancy closing-date status fix
- An **Open** vacancy whose closing date is already past is forced to **Closed**.
- If a vacancy was **Closed because its previous deadline expired**, changing/removing that old deadline so the application window is active again automatically changes the status back to **Open** on save.
- A vacancy intentionally closed early by HR remains **Closed** even if its future deadline is edited. This prevents a manual HR decision from being silently overridden.
- The Edit Vacancy modal previews the automatic reopen before saving.
- Existing applicant assessments are recalculated after vacancy qualification changes.

## 2. Fixed system-managed employment forms
Assessment V5 uses only these two fixed forms:
1. **Application for Employment**
2. **Employment Questionnaire**

They are system-managed and protected from accidental edit/delete in the Form Templates screen. Custom templates may still exist for other workflows, but Assessment Insights never silently falls back to an arbitrary form.

## 3. Structured Application for Employment evidence

### Job Application Data
- Availability to Start uses controlled options.
- **Specific Available Start Date** appears only when `Specific Date` is selected.
- Required **Schedule / Shift Readiness**.
- Required **On-site Readiness**.
- Referral name appears only for `Employee Referral`.

### Personal Information
- Age is auto-calculated from Birthdate and is never trusted as a separately typed value.
- Blood Type is a controlled ABO/Rh selection.
- Sensitive/demographic fields remain excluded from Assessment Insights scoring.

### Educational Background
- **College / Degree Completion Status is required**, including `No College / Not Applicable`.
- Graduation / expected graduation year is structured.
- Free-text `Other Professional Qualifications` is replaced by up to two structured professional credential records:
  - Credential/license name
  - Type
  - Issuer
  - Current status
  - Valid-until date
  - Optional credential/license number for HR verification
- Free-text `Relevant Certifications / Trainings` is replaced by up to two structured certification/training records:
  - Certification/training name
  - Type
  - Provider/issuer
  - Completion/validity status
  - Completion date
  - Optional certificate/credential number
- Required Yes/No declarations distinguish **truly none** from **missing evidence**.
- Applicant-reported credential status is still subject to HR verification; Assessment Insights does not claim independent credential verification.

### Employment History
- Required Yes/No declaration for previous/current work experience.
- Each of two fixed employer records separates:
  - Company
  - Position
  - Start/end/current-employment status
  - Primary responsibilities
  - Exact tools/software/systems used
  - Key achievements/results
- If the applicant declares work experience, at least one complete record is required.
- End date cannot precede start date.
- Current employer disables the end date.
- Overlapping employment periods are counted once.
- Experience shown in Assessment Insights is described as **date-supported**, not independently employer-verified.

### Role-Specific Assessment
- Exact tools/software/systems actually used are captured separately from general skill claims.
- Problem solving is split into:
  1. Problem / Situation
  2. Action
  3. Result
- This improves English and Filipino/Taglish evidence extraction and prevents the system from guessing which sentence is an action or outcome.

## 4. Assessment Reliability V5 logic
- Model version increased to **5**. Old results auto-recalculate when Assessment Insights is opened.
- `Not Met`, `Not Evidenced`, and `Needs Verification` remain separate states.
- Explicit `No work experience` can establish a real experience gap instead of being treated as missing data.
- Explicit `No professional credential` / `No certification or training` can establish a required credential gap when the vacancy clearly requires that credential type.
- Degree discipline and **degree completion** are assessed separately.
- Expired required licenses do not satisfy a current/valid license requirement.
- A certification named after software does **not** prove actual software usage.
- General skill lists that only name a tool do **not** automatically prove actual usage.
- Exact tool usage from role-specific and employment fields is stronger evidence.
- Availability, schedule, and on-site requirements use structured readiness fields rather than inference.
- Vacancy role context now includes normalized structured qualification records as well as legacy qualification text.
- Education/credential context and availability context remain non-scoring unless explicitly required by the vacancy, preventing double counting.
- Assessment Reliability remains evidence quality/coverage, **not** a probability of hiring success or future performance.

## 5. Validation and compatibility
- Existing field IDs are updated in place where possible so historical `FormAnswer` references are preserved.
- Legacy free-text credential answers are kept as **status unknown** rather than silently promoted to valid/current evidence.
- Migration rollback is intentionally non-destructive to protect historical answers.

## Deployment
After replacing the project files, run:

```bash
php artisan migrate
php artisan optimize:clear
```

The migration is required for the new structured application fields to appear in an existing database.

## Checks completed in the delivery environment
- PHP syntax checks passed for all V5 changed PHP/Blade files.
- 46/46 manual Assessment V5 regression checks passed.
- 3/3 vacancy deadline/status regression checks passed.
- Changed Blade templates compiled successfully using Laravel's Blade compiler.
- Assessment and vacancy routes were resolved successfully.

Full PHPUnit / normal `artisan view:cache` could not run in the delivery container because its PHP build is missing `dom`, `mbstring`, `xmlwriter`, and a PDO database driver. No production polyfills were added; the application should use the normal PHP extensions on XAMPP/Hostinger.
