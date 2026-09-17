# Automated Assessment Insights V2

## What changed

Assessment Insights is now a fixed, automatic workflow. HR/Admin no longer needs to create assessment criteria, assign weights, choose vacancy forms, or map questions manually.

### Fixed evidence source

The system automatically uses the active canonical forms:

1. **Application for Employment**
2. **Employment Questionnaire**

The applicant form and Assessment Insights use the same form-selection method so they cannot drift apart. Extra active templates are not silently included in scoring.

### Automatic scoring model

Final Role Fit uses:

- **45% Vacancy Qualification Match**
- **55% Supporting Role Evidence**

The supporting block uses:

- Relevant Experience — 25%
- Role-Specific Skills — 25%
- Problem Solving & Results — 20%
- Role Evidence Quality — 15%
- Education & Certifications — 10%
- Availability & Readiness — 5%

These supporting weights are applied only to evidence areas that are actually scorable. Missing or unverifiable evidence is N/A instead of receiving artificial neutral points.

## Reliability improvements

- Required vacancy qualifications have explicit matched / not matched / needs verification states.
- Required qualification failures are conservatively capped and labeled **Needs Review** rather than appearing as a normal fit.
- Low qualification coverage produces **Insufficient Evidence** instead of a misleading high-fit label.
- Strong Fit now requires both a high Role Fit score and high evidence confidence.
- Tool/software requirements such as "experience using NetSuite/QuickBooks" are treated as skills, not employment-duration requirements.
- Skill qualification matching uses role/skills/project/duties evidence; a degree title alone is not treated as proof of a job skill.
- Existing legacy metadata that incorrectly classified tool requirements as experience is repaired at assessment time.
- Education matching is discipline-aware; for example, BS Information Technology does not match an Accountancy/Finance requirement merely because both contain generic words such as "technology".
- A degree/certification does not add Role Fit points when the vacancy has no explicit education/certification requirement.
- Employment duration only scores when dates are structurally verifiable.
- Role Evidence Quality excludes culture/preference questions from Role Fit.
- Problem-solving/action/result detection recognizes common English and Filipino/Taglish evidence wording.
- Sensitive/demographic fields are excluded from scoring.
- Resume presence does not add automatic confidence points because file presence alone is not verified role evidence.
- Evidence confidence is based on evidence coverage and vacancy readiness.

## Automation triggers

Assessment is refreshed automatically when:

- a new applicant submits the fixed forms;
- HR/Admin edits the applicant's submitted evidence or moves the application to another vacancy;
- HR/Admin updates vacancy qualifications;
- Assessment Insights first encounters a legacy score from an older assessment model.

A **Refresh Scores** button remains as a manual fallback, not as a setup requirement.

## Comparison redesign

Candidate Comparison is intentionally compact:

- compare exactly two applicants using two dropdowns;
- show only decision-relevant evidence areas;
- use green/red only when both candidates have comparable scored evidence;
- keep missing evidence neutral as N/A;
- show the largest evidence differences in a concise summary;
- keep qualification-by-qualification evidence inside the individual candidate insight, not in the side-by-side table.

## Important hiring note

Assessment Insights remains decision support. Applicant answers are self-reported unless separately verified by HR. Interview and document verification should remain part of the final hiring process.
