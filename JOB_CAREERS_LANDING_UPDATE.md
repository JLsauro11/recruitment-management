# RS8 Recruitment - Careers Landing Page Update

## Public careers experience

- Public root `/` sends applicants to `/careers`.
- The Careers page has been redesigned into a lighter editorial recruitment site instead of a dark card-heavy dashboard layout.
- The new visual direction uses large typography, open spacing, a soft white / warm-gray canvas, RS8 red accents, editorial content sections, and list-style vacancy rows.
- Job openings are shown as clean rows with poster thumbnails, department, employment type, slots, closing date, and an Apply action.
- Search and department filtering remain available.
- Latest open vacancies remain at the top.
- Optional job posters uploaded by Admin are used as the vacancy thumbnail.
- The hero and culture sections use open-licensed CC0 vector references from SVG Repo loaded from the public web.
- The layout is responsive for desktop, tablet, and mobile.

## Vacancy setup

- Position labels use the format `Position | Department`, for example `Videographer | Boss J - Corporate Services`.
- Vacancy Title automatically follows the selected Position and is also enforced server-side.
- Add/Edit Job Vacancy supports a job-position poster upload and preview.
- Admin tables that previously exposed database `ID` now use ascending display numbers while keeping newest records first.

## Secure application entry protection

- Applicants must choose a vacancy from the Careers page first.
- The selection is performed through a CSRF-protected POST action.
- The chosen vacancy is stored server-side in the applicant session for up to 2 hours.
- `/apply` redirects back to Careers when there is no valid selected vacancy in session.
- Direct legacy URLs such as `/apply/{vacancy}` are blocked and sent back to Careers.
- The application form does not accept or trust a vacancy ID from the URL or a hidden form field.
- The selected vacancy is attached automatically to the application.
- The selection is cleared after a successful submission.

## Deployment note

This visual redesign adds no new database migration beyond the previously included `poster_path` migration. If that migration has not yet been run on the server, run:

```bash
php artisan migrate --force
```

If the public storage link for uploaded job posters does not exist yet, run once:

```bash
php artisan storage:link
```
