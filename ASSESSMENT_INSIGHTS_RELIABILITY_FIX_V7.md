# Assessment Insights Reliability Fix V7

This update corrects the evidence-pipeline and scoring issues found while reviewing the IT Specialist demo comparison in V6.

## Critical fixes

1. **Employment evidence is no longer accidentally discarded.**
   - Privacy filtering now uses exact personal-field keys instead of broad `name` / `address` substring matching.
   - `Company Name` and `Company Address` remain valid employment-record completeness evidence.

2. **Situation / Action / Result fields are forward-repaired on existing databases.**
   - New migration `2026_09_08_180000_fix_assessment_evidence_pipeline_v7.php` restores `role_problem_solving`, `role_problem_action`, and `role_problem_result` even when an older migration was already marked as executed.
   - This prevents a seeded or newly submitted Action answer from silently disappearing from Assessment Insights.

3. **Qualification percentages no longer hide pending requirements.**
   - `score` is the evidence-supported match across the full weighted requirement set.
   - `assessed_match_rate` is kept separately for the percentage among requirements that can already be decided.
   - A candidate with 4 assessed matches and 1 pending required item can no longer appear as a misleading perfect overall qualification score.

4. **Related-degree logic is separated from document verification.**
   - When a vacancy explicitly allows a `related field`, a clearly adjacent degree such as Computer Engineering for an IT/CS/IS requirement can satisfy role-fit matching.
   - HR can still verify the diploma/TOR independently; document verification is not confused with role-fit matching.

5. **Compound qualifications are assessed component-by-component.**
   - Example: `Windows troubleshooting, hardware/software support, and basic networking` is decomposed into separate evidence components.
   - Mentioning only `Windows` cannot satisfy the whole requirement.

6. **Role-specific skills use canonical competencies instead of raw keyword counts.**
   - Repeated words are deduplicated into competency families such as Windows support, hardware/endpoint support, networking, system administration, documentation, user support, and troubleshooting.
   - Named vacancy tools/systems are counted separately from generic competencies.

7. **Relevant Experience title-family matching is more conservative and role-aware.**
   - IT Support, Help Desk, System Administration, and closely related support titles are recognized as the same role family without relying only on stop-word overlap.
   - Overlapping employment intervals are still counted once.

8. **Assessment Reliability is penalized when critical evidence is incomplete.**
   - Missing or weak SAR Action/Result evidence now lowers evidence coverage/quality and caps reliability instead of being hidden by complete answers elsewhere.

9. **Comparison UI is clearer.**
   - Missing/unverified evidence is described as separate from confirmed failure.
   - Qualification comparison shows evidence-supported score, match among assessed requirements, and evidence coverage separately.
   - Compound requirements show component statuses in the head-to-head evidence table.
   - Candidate Ranking now includes Problem Solving as a visible fit row.

## Upgrade commands

```bash
php artisan migrate
php artisan db:seed --class=ItSpecialistSampleApplicantsSeeder
php artisan optimize:clear
```

Re-running the IT Specialist sample seeder is recommended after migration because it backfills the demo applicants' structured Action/Result answers into the newly guaranteed form fields. The seeder uses update-or-create behavior for its dedicated demo vacancy/applicants.

## Validation completed in the build environment

- PHP syntax validation: 80 project PHP files passed.
- Assessment V6 compatibility checks: 18/18 passed.
- Assessment V7 reliability regression checks: 14/14 passed.
- Blade templates: 28 templates compiled successfully using Laravel's Blade compiler.
- Full PHPUnit could not run in the build environment because PHP extensions `dom`, `mbstring`, and `xmlwriter` are unavailable there.
