# Landing Login + Job Detail Alignment Update

## Staff login integration
- Staff Login now opens directly inside the public Careers landing page as a light-theme, Figma-inspired modal workspace.
- Direct visits to `/login` redirect to `/careers?staff_login=1`, which automatically opens the same integrated login UI.
- Existing POST `/login` authentication remains unchanged and returns the normal role dashboard redirect.
- Login modal includes inline validation, password visibility toggle, secure access messaging, responsive mobile behavior, Escape/backdrop close, and keyboard focus support.

## Open positions alignment
- Job detail rows now use a fixed icon column and a separate text column.
- Clock, people, and calendar icons share the same 24x24 centered icon area.
- The details group has a consistent max width and vertical alignment across vacancies.

## Files changed
- `app/Http/Controllers/AuthController.php`
- `resources/views/layout/applicant.blade.php`
- `resources/views/applicant/careers/index.blade.php`
