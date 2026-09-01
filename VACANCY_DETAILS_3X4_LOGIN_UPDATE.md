# Vacancy Details, 3:4 Poster, and Login Cleanup

## Public Careers
- Job cards now open a vacancy details modal first instead of selecting the vacancy immediately.
- The details modal shows the 3:4 poster, position/department, employment type, slots, opening/closing dates, salary, and qualifications.
- `Apply now` is the only action that stores the selected vacancy and continues to the employment application.
- Public job posters are displayed in an exact 3:4 portrait ratio.
- Job description copy was removed from the job cards/details flow.

## Vacancy Admin
- Job Description field removed from Add/Edit Job Vacancy.
- Qualifications are now required.
- Poster uploads accept JPG/JPEG/PNG/WEBP up to 5 MB and must pass backend `dimensions:ratio=3/4` validation.
- Added immediate browser-side aspect-ratio feedback for convenience; backend validation remains authoritative.
- Poster preview updated to 3:4.

## Staff Login
- Simplified the integrated Figma-inspired RS8 Light login modal.
- Removed the busy recruitment overview board and extra detail blocks.
- Close button now uses a high-contrast visible multiplication sign instead of relying on an icon glyph.
- Responsive behavior retained for desktop, tablet, and mobile.

## Database
- No migration required. The existing `description` column remains for compatibility but the vacancy UI no longer uses it; new/updated vacancy records clear it to null.
