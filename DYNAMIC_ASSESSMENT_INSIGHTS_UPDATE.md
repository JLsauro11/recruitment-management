# Dynamic Assessment Insights Update

## Added
- Fully dynamic vacancy assessment profiles; no position names are hardcoded.
- Position-default assessment profiles that future vacancies can inherit.
- Vacancy-specific customization without changing other vacancies of the same position.
- Dynamic assessment criteria: name, weight, importance, minimum score, required flag, description.
- Vacancy-specific Form Template assignment.
- Backward-compatible form fallback: if a vacancy has no assigned templates, all active templates continue to be used.
- Form Question -> Assessment Criterion mapping.
- Per-question scoring method: Auto, Text + Rubric, Exact Choice, Presence.
- Per-question maximum points, rubric/evidence guide, answer key, and critical/knockout flag.
- Assessment Readiness score and evidence coverage.
- Candidate score confidence now depends on mapped evidence coverage and setup readiness.
- Sensitive demographic fields remain excluded from scoring.
- Premium tabbed Assessment Insights UI for Ranking, Criteria, Vacancy Forms, and Question Mapping.

## Database
Run after replacing project files:

    php artisan migrate

New migration:
- 2026_09_05_002000_create_dynamic_assessment_profiles.php

## Recommended configuration flow
1. Open Assessment Insights.
2. Choose a vacancy.
3. Configure Criteria and make weights total 100%.
4. Save as Position Default if future vacancies of the same position should inherit it.
5. Select Vacancy Forms.
6. Map scorable questions to criteria and add rubrics where needed.
7. Recalculate candidate ranking.
