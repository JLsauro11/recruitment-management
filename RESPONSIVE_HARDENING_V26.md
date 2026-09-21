# RS8 Recruitment Responsive Hardening V26

This update adds a shared mobile-responsiveness safety layer across the public applicant flow and the Admin/HR interface.

## Priority fixes
- Applicant Application for Employment form now reflows cleanly on phones and tablets.
- Application progress/sidebar becomes a compact two-column step navigator on phones.
- Form fields become single-column, full-width, 48px touch targets with 16px mobile input text.
- Job context, upload areas, record headers, review panel, and action buttons are mobile-safe.
- Previous/Continue/Submit actions become a sticky bottom action area on phones.
- Admin/HR cards, headers, modals, tables, DataTables controls, notification menus and Assessment Insights are hardened against horizontal page overflow.
- Assessment comparison controls stack on phones; wide evidence matrices use intentional contained horizontal scrolling instead of shrinking into unreadable text.
- Long candidate/evidence text safely wraps.

## Breakpoints
- Large desktop: > 1200px
- Tablet/small desktop: <= 991.98px
- Mobile: <= 767.98px
- Small mobile: <= 575.98px
- Narrow phone: <= 359.98px

## Deployment
No database migration is required for this update. After deploying the changed files, run:

```bash
php artisan optimize:clear
php artisan view:cache
```

If CSS is served through an external cache/CDN, clear that cache as well.
