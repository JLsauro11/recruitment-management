# RS8 Recruitment Management System - Completed Modules

## Login accounts from the seeder
- Admin: `admin@rs8.com` / `password`
- HR: `hr@rs8.com` / `password`
- New applicant form account: `applicant@rs8.com` / `password`
- Seeded applicant accounts also use `password`.

## Admin
- Dashboard
- Applicants and status updates
- Interview scheduling and management
- Exam result recording
- Hiring pipeline/status management
- Job vacancies
- Positions
- Departments
- User accounts
- Recruitment reports with CSV export
- System settings

## HR
- Dashboard
- Applicant management
- Interview scheduling and management
- Exam result recording
- Hiring pipeline/status management
- Recruitment reports with CSV export

## Applicant
- No dashboard
- Applicant is redirected directly to the employment application form
- Personal information form
- Open vacancy selection
- Resume upload
- Submitted application list/status

## Setup after replacing the project

```bash
composer install
php artisan optimize:clear
php artisan migrate --seed
php artisan storage:link
```

For a clean database:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```
