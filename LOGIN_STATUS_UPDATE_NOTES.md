# Login + Vacancy Status Update

## Vacancy status behavior
- Open vacancies with a closing date before today are automatically changed to `Closed` when vacancy data is loaded.
- Public careers/application queries only include vacancies whose opening date has arrived and whose closing date has not passed.
- Admin/HR dashboards and recruitment reports count only vacancies that are currently accepting applications.
- Saving a vacancy as `Open` with an already-past closing date automatically normalizes it to `Closed`.
- Vacancy details shown in applicant management use the effective status.

## Login redesign
- Rebuilt the staff login page in a light, Figma-inspired RS8 layout.
- Preserved existing login endpoint, AJAX behavior, validation, password visibility toggle, and SweetAlert feedback.
- Uses local project assets for jQuery, Bootstrap Icons, and SweetAlert.
- Responsive desktop/tablet/mobile layout.

## Status capitalization
- Department, Position, and User Account status badges now display in sentence/title case (`Active`, `Inactive`) while preserving lowercase database values.
