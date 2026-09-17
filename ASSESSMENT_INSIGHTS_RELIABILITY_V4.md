# Assessment Insights Reliability V4

## Goal
Assessment Insights is now designed as automatic HR decision support using only the fixed **Application for Employment**, **Employment Questionnaire**, and the qualifications stored on each Job Vacancy. HR/Admin no longer needs to create criteria, weights, form assignments, or question mappings.

## Final scoring model
- **45% Vacancy Qualification Match** — evaluates each vacancy requirement one-by-one.
- **55% Supporting Role Evidence**
  - Relevant Experience: 45% of the supporting block
  - Role-Specific Skills: 40% of the supporting block
  - Problem Solving & Results: 15% of the supporting block
- Evidence Quality affects **Assessment Reliability only**, not Role Fit.
- Education/Certification and Availability rows are contextual unless the vacancy explicitly makes them requirements; explicit requirements are scored once under Vacancy Qualification Match to avoid double counting.

## Reliability safeguards added in V4
- `Not met`, `Not evidenced`, and `Needs verification` are separate states.
- Missing evidence never receives an artificial 50% or invented partial score.
- Confirmed required-qualification failures are treated differently from missing proof.
- Strong/Good fit labels require both sufficient Role Fit and sufficient Assessment Reliability.
- Ranking prevents a confirmed required gap from outranking a candidate whose required item is only pending verification.
- Assessment Reliability means evidence quality/coverage, **not** probability of being hired or succeeding in the role.
- Applicant claims remain subject to interview/reference/document verification.

## Qualification matching improvements
- Hard requirement language (`must`, `required`, `minimum`, `at least`) takes priority over an embedded preferred clause.
- Embedded preferences such as `NetSuite is an advantage` are tracked separately and used only as a tie-breaker.
- A preferred tool cannot replace a different required tool.
- Purely preferred qualifications are still directly assessable.
- Generic words such as `system`, `accounting`, `attention`, or `detail` do not automatically prove a requirement.
- Named accounting tools and conservative same-category `or similar` matching are supported.
- Domain-specific tools not in the built-in catalog (for example a POS system) can still be evaluated when the applicant gives concrete usage evidence.
- `AND`-connected named tools/credentials require complete evidence; `OR` alternatives do not.
- Training-gap answers can trigger verification but never count as positive proficiency evidence.
- Unrelated certifications do not prove that a required credential is absent.

## Experience improvements
- Role relevance uses title, duties, role concepts, and vacancy context instead of generic keyword counts.
- Employment duration uses submitted start/end/current-employment dates.
- Overlapping employment periods are merged and counted once.
- Meeting a minimum experience requirement is assessed in Qualification Match; the supporting Relevant Experience score measures depth, relevance, duties, and outcomes so the same minimum is not simply awarded twice.

## Problem-solving improvements
- Detects Situation, Action, and Result evidence separately.
- Expanded Filipino/Taglish action/result patterns (`nireview`, `kinumpara`, `sinuri`, `inayos`, `natukoy`, `nalaman`, etc.).
- Numbers such as `2 years` do not count as performance results by themselves.
- Concrete actions and outcomes have more weight than answer length.

## Education and availability improvements
- Degree discipline matching is stricter; unrelated degrees are not accepted through generic word overlap.
- `related field` cases can be routed to `Needs verification` instead of forced pass/fail.
- Availability only verifies what the fixed form actually captures.
- Onsite/shift/relocation requirements are not falsely inferred from start availability.
- Conflicting availability and notice periods use the later/conservative start window.
- `More than 30 Days` is treated as open-ended and is not falsely assumed to satisfy a longer deadline such as 60 days.

## Fairness / scope controls
- Sensitive and demographic fields are excluded from scoring.
- Culture-preference answers are excluded from Role Fit and Assessment Reliability.
- Current employment status itself does not increase or reduce Role Fit.
- Availability affects Role Fit only when the vacancy explicitly states a start-timing requirement.

## Head-to-head comparison improvements
- Shows exact qualification-by-qualification status and evidence for each candidate.
- Separates confirmed failure from missing evidence.
- Shows preferred advantages separately.
- Reliability/context rows are not colored as scored strengths/weaknesses.
- Key Differences prioritizes required-qualification status before raw score differences.
- Candidate Ranking shows confirmed required gaps and required items pending verification.

## Automation
- Existing V3/older assessment results automatically recalculate to V4 when Assessment Insights is opened.
- New/edited application responses automatically reassess the applicant.
- Vacancy qualification edits automatically reassess existing applicants.
- No new database migration is required for V4.

## Verification performed for this delivery
- PHP syntax checks passed for the modified service, controllers, Blade source, seeder, and tests.
- Blade view compiled successfully and the compiled PHP passed syntax validation.
- Assessment Insights routes were resolved successfully.
- A standalone assessment regression harness completed **32/32 checks** covering false-positive and false-negative cases for tools, requirements, credentials, availability, education, Filipino/Taglish evidence, overlapping experience, and fit gating.

The delivery environment does not provide the PHP `dom`, `mbstring`, `xmlwriter`, or PDO database driver extensions required to run the project's full PHPUnit/database integration suite. The standalone regression harness uses test-only mbstring polyfills; production application code was not changed to work around missing server extensions.
