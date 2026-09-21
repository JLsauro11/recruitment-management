# Static Employment Application Update

The public recruitment form is now code-defined to keep Assessment Insights stable.

## What changed

- Added `app/Services/StaticEmploymentFormService.php` as the single source of truth for all application fields and questionnaire questions.
- Applicant-facing field labels, types, options, required flags, order, and widths are overlaid from code rather than trusted from editable database metadata.
- Assessment Insights now loads the same static form definition used by the applicant form.
- `Form Templates` was removed from the Admin/HR sidebar and its management routes/controller/view were removed.
- Existing `form_templates`, `form_fields`, `form_submissions`, and `form_answers` tables are intentionally retained so historical applicant answers and stable field IDs remain compatible.
- Added migration `2026_09_17_142000_lock_employment_forms_to_static_code.php` to upsert the code-defined fields without deleting legacy rows.
- `FormTemplateSeeder` now uses the same static service, avoiding a second copy of the question definitions.

## Future question changes

Edit only `app/Services/StaticEmploymentFormService.php`, then add/run a migration or run the static form seeder to sync the database metadata. Keep existing `field_key` values unchanged when only rewording a question so Assessment Insights and historical answers remain compatible.

## Production deployment

After uploading/pulling the updated code:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

Do not use `migrate:fresh` on production.
