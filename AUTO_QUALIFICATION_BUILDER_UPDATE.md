# Automatic Job Qualification Builder Update

- Simplified Add/Edit Job Vacancy so HR only enters each qualification text.
- Kept **Add Qualification** for multiple requirements.
- Removed manual Type, Requirement Level, Importance, Minimum Value/Unit, and Evidence Source controls from the vacancy modal.
- Added automatic live preview badges so HR can see what the system inferred without configuring it.
- Server-side inference is authoritative and runs again when the vacancy is saved.
- Automatically detects Experience, Education, Certification, Availability, or Skill requirements.
- Automatically extracts numeric experience minimums such as `2 years` or `6 months`.
- Automatically recognizes Preferred / Nice-to-Have wording and assigns appropriate assessment priority.
- Automatically selects the appropriate applicant evidence source used by Assessment Insights.
- Existing structured qualification records remain compatible and will be re-inferred from their qualification text on the next save.
- No database migration is required for this UI/logic update.
