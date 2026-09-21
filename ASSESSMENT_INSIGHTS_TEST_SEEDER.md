# Assessment Insights Test Seeder

A dedicated test-data seeder is available for validating the static application questions and Assessment Insights pipeline.

## What it creates

- 3 IT Specialist sample applicants
- 3 Accounting Associate validation applicants
- 3 Inventory Control Officer validation applicants
- Complete static Application for Employment answers
- Complete static Employment Questionnaire answers
- Structured vacancy qualifications
- Assessment Insights results generated immediately by `AssessmentInsightService`

The sample answers intentionally contain different evidence strengths so you can inspect whether the engine distinguishes stronger, moderate, and developing candidates based on role-specific evidence instead of generic answers.

## Run on Hostinger / production manually

```bash
php artisan db:seed --class=AssessmentInsightsTestSeeder --force
php artisan optimize:clear
```

The seeder is repeatable: it uses stable sample emails, vacancy titles, and reference numbers, so running it again updates the same test dataset instead of creating endless duplicates.

## Where to look

Open Recruitment > Assessment Insights and look for vacancies with these titles:

- `IT SPECIALIST - ASSESSMENT DEMO`
- `ACCOUNTING ASSOCIATE - ASSESSMENT VALIDATION`
- `INVENTORY CONTROL OFFICER - ASSESSMENT VALIDATION`

Reference numbers start with:

- `RS8-DEMO-IT-`
- `RS8-VAL-ACC-`
- `RS8-VAL-INV-`

## Static-question safety

Before inserting answers, the seeder calls `StaticEmploymentFormService::syncToDatabase()`. This guarantees that the test answers use the same stable field keys currently expected by the live static application form and Assessment Insights.
