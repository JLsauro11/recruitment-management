# Assessment Insights + Applicant Form Layout Fix

## Assessment scoring
- Missing/unanswered mapped criteria no longer receive an artificial 35% score.
- Missing evidence is shown as N/A and reduces confidence instead of distorting role-fit.
- Structured employment/education/availability fields use structured scoring rather than essay-length scoring.
- Questionnaire Quality scores answer substance/completeness separately from role alignment.
- Ranking is explicitly marked Provisional while Assessment Readiness is incomplete.
- Existing demographic exclusions remain in place.

## Applicant PDF / Print Forms
- Replaced CSS column/masonry flow with section-based grids for stable alignment.
- Questionnaire uses a clean two-column question/answer card layout with left-aligned long answers.
- Resume/CV attachment filename is excluded from the printable application pages, preventing the stray mostly-empty extra page.
- Every form template is constrained to a true A4 page so the footer stays at the bottom.
- Page numbering now corresponds to submitted form templates when content fits the A4 design.

## Migration compatibility
- Shortened the long assessment field mapping foreign-key constraint name for MySQL compatibility.
