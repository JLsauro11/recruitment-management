# RS8 Recruitment Final Release V24

## Final status
This package is the cleaned final handoff of the RS8 Recruitment Management System with Gemini-powered semantic evidence interpretation and deterministic assessment scoring.

## Assessment architecture
- Gemini interprets applicant evidence semantically, including English, Tagalog, and Taglish.
- Gemini does not directly assign the final Role Fit score.
- Qualification status, weights, scoring, fit labels, ranking, and comparisons remain deterministic and auditable in Laravel.
- Ambiguous or unsupported evidence is not converted into positive evidence.
- AI failure falls back to deterministic rules rather than silently producing a zero score.

## Language-neutral scoring safeguards
- Equivalent English / Tagalog / Taglish evidence is normalized into a canonical competency set.
- Synonym count, verbosity, and translation length do not add points.
- Structured problem solving is scored using bounded semantic evidence categories.
- Role Evidence Quality is separated from Role Fit scoring.
- Required and preferred qualifications remain separate; preferred evidence is a tie-breaker / optional advantage and not a hard penalty.

## Final QA completed
- 97 PHP files passed syntax linting.
- Gemini language-invariance tests passed.
- Canonical scoring-input tests passed.
- Hardware/software mapping regression passed.
- Structured problem-solving paraphrase invariance passed.
- Weak/brief SAR evidence cap passed.
- Candidate comparison UI test passed all 13 assertions and both inline scripts parsed.
- Project secret scan completed; production credentials were removed from the distributable package.
- IDE-specific `.idea` metadata and local backup env files were removed from the final handoff.

## Runtime limitation of this packaging environment
The final package intentionally excludes `vendor/`. The current packaging environment also does not provide the Composer executable, so a full Laravel HTTP boot / PHPUnit suite could not be run here. The application-level static, manual semantic, and UI regression tests included with the project were run successfully.

## First run on the development machine
```bash
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan optimize:clear
npm run build
```

For the Gemini assessment interpreter, copy `.env.example` or `.env.hostinger.example` to the appropriate environment file and provide your own `GEMINI_API_KEY`.

Then refresh Assessment Insights scores from the admin page, or run:
```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```

## Optional language regression test data
```bash
php artisan db:seed --class=GeminiLanguageAssessmentTestSeeder
```
Then open **IT SPECIALIST - GEMINI LANGUAGE TEST** and click **Refresh Scores**.
