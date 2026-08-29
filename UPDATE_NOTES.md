# Employment Forms Update

## Major changes

- Removed the Exam Results module from Admin and HR.
- Added manageable Form Templates for:
  - Application for Employment
  - Employment Questionnaire
- Added Submitted Forms viewer for Admin and HR.
- Applicant accounts no longer have a dashboard. They are redirected directly to the combined employment application and questionnaire.
- Form fields are database-driven and can be added, edited, reordered, required, activated, or deactivated.
- Applicant responses are stored by application and template.
- Hiring stage `For Examination` was replaced with `For Questionnaire Review`.

## Required installation command

Because the recruitment schema and seeded templates changed, use:

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan storage:link
```

Default accounts use the password `password`:

- admin@rs8.com
- hr@rs8.com
- applicant@rs8.com
