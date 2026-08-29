# Applicant Information Editing Update

Added editable applicant information directly from the Applicants page.

## Included
- New **Edit Applicant Information** action for both Admin and HR.
- Edit the selected job vacancy.
- Edit all submitted Application for Employment fields.
- Edit submitted questionnaire answers.
- Replace an uploaded resume/document while keeping the current file when no replacement is selected.
- Applicant profile fields are synchronized after saving (name, email, mobile, address, birthdate, gender, and resume).
- Validation prevents duplicate applicant emails and duplicate applications for the same applicant + vacancy.
- Existing status update, print forms, resume viewing, interview, and delete actions remain available.

## Files Updated
- `app/Http/Controllers/Admin/ApplicantController.php`
- `routes/web.php`
- `resources/views/admin/applicants/index.blade.php`
