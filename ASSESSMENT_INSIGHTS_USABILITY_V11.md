# Assessment Insights V11 — final usability calibration

V11 keeps the V10 Role Fit model and fixes the last misleading optional-evidence presentation.

## What changed

- Optional/preferred evidence breadth is now literal coverage when a preferred requirement lists named technologies/components.
  - 4 of 4 technologies = 100% internal tie-breaker breadth.
  - 1 of 4 technologies = 25%, not the previous synthetic 78%.
- The UI no longer presents a synthetic "Optional evidence depth" percentage.
- Preferred rows now show the exact evidence count, such as `4/4 named optional technologies evidenced` or `1/4 named optional technologies evidenced`.
- Head-to-head summaries name each candidate's exact optional evidence instead of saying `100% vs 78%`.
- Optional evidence remains a tie-breaker only and never changes the required-qualification baseline or Role Fit score.
- Optional evidence that still needs verification gets no ranking advantage until it becomes sufficiently evidenced.
- Assessment model version is bumped to 11 so older cached V10 results are recalculated consistently.

## What did not change

- Required qualifications remain 45% of Role Fit.
- Supporting role evidence remains 55% of Role Fit.
- Preferred/nice-to-have qualifications do not add Role Fit points.
- Overall Evidence Quality remains separate from truth/document verification.
- HR review continues to take precedence for confirmed or pending required gaps.

## Updating an existing V10 install

No new migration is required.

```text
php artisan optimize:clear
```

Then open Assessment Insights or use Refresh Scores. V11 automatically replaces older assessment-model results.
