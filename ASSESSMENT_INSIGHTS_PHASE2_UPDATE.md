# Assessment Insights - Phase 2

## Added
- Custom assessment criteria per Position
- Editable criterion name, assessment source, weight, and keywords
- Questionnaire question-to-criterion mapping
- Server-side validation requiring criteria weights to total exactly 100%
- Recommended Phase 1 fallback profile when no custom criteria exist
- Reset custom profile back to recommended defaults
- Automatic applicant reranking after criteria save/reset
- Premium responsive criteria setup modal
- Weighted category labels shown directly in every candidate score breakdown

## Assessment Sources
- Role / Skill Match
- Relevant Experience
- All Questionnaire Answers
- Mapped Questions
- Education / Qualifications
- Profile Completeness

## Database
New tables:
- assessment_criteria
- assessment_question_mappings

Run only:
php artisan migrate

Do NOT run migrate:fresh on a database that contains applicant records unless you intentionally want to erase all data.

## Notes
Criteria are attached to the Position, so the same assessment profile is reused by all vacancies under that position. Applicant demographic attributes such as age, gender, religion, and address are not used directly in the scoring engine.
