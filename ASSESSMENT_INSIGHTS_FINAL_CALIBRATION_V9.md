# Assessment Insights Final Calibration V9

## Goal
V9 is the final calibration pass for the automatic RS8 Recruitment Assessment Insights. It keeps the simplified applicant workflow while making ranking and head-to-head comparison more internally consistent, explainable, and resistant to double-counting.

## V9 corrections

### 1. Overall Evidence Quality vs Role Evidence Quality
The legacy `confidence` database value remains for backward compatibility, but the interface now calls it **Overall Evidence Quality**. It summarizes assessment-wide evidence completeness, specificity, and assessable coverage.

The non-scoring questionnaire-only row is now explicitly named **Role Evidence Quality**. It measures the quality of the role-focused questionnaire only.

Neither percentage means the applicant's claims are verified or predicts job performance. HR/document/reference verification remains separate.

### 2. Preferred / nice-to-have requirements are true tie-breakers
Preferred items remain outside the 45% required-qualification baseline and do not lower Role Fit when absent.

V9 adds **Optional Evidence Depth** so HR can distinguish candidates who both satisfy the same preferred item. Example: if a preferred requirement lists Microsoft 365, Active Directory, DNS, and DHCP, a candidate with direct evidence for all four shows broader optional evidence than a candidate with only Microsoft 365. This depth is shown as tie-breaker context only and never changes the required-qualification or Role Fit score.

### 3. Optional tools no longer inflate Role-Specific Skills
Role-Specific Skills is part of Role Fit, so V9 scores it only against:
- the vacancy title / position family; and
- **required** vacancy qualifications.

Preferred/nice-to-have competencies and tools are detected and displayed separately as optional evidence, but they no longer increase the Role-Specific Skills numeric score. This prevents optional requirements from being double-counted as hidden Role Fit points.

### 4. Competency overlap cleanup
`Documentation` no longer also creates a false `Data entry / records` competency. The two concepts are separate.

`User support` recognition was tightened so hyphenated `user-support` is recognized without using the overly broad `technical support` alias in two different canonical competencies.

### 5. Head-to-head comparison improvements
The comparison now:
- distinguishes Overall Evidence Quality from Role Evidence Quality;
- shows optional evidence depth per preferred row;
- identifies which candidate has broader optional evidence when both have the same preferred status;
- states clearly that optional evidence is a tie-breaker only;
- continues to prioritize required gaps / required verification over raw Role Fit score.

## Expected effect on the IT Specialist demo
The V8 demo could give Role-Specific Skills extra credit for Microsoft 365 / Active Directory / DNS / DHCP even though those were configured as preferred. V9 removes that hidden boost.

For the seeded Marco and Andrea examples:
- both still have a 100% required-qualification baseline when all required items are evidenced;
- Marco has broader optional technology evidence than Andrea;
- that optional breadth is visible as a tie-breaker but does not affect either Role Fit score;
- required Role-Specific Skills remain directionally stronger for Marco because of stronger required-role evidence and applied examples.

## Model version
`AssessmentInsightService::MODEL_VERSION = 9`

Existing assessment results automatically recalculate when Assessment Insights is opened or when Refresh Scores is used because older category scores have a lower model version.

## Database changes
No new database migration is required when upgrading from V8.

After replacing the project files, run:

```bash
php artisan optimize:clear
```

Then open Assessment Insights or click **Refresh Scores**.

## Validation completed
- Assessment V6 regression checks: 18/18 passed
- Assessment V7 reliability checks: 14/14 passed
- Assessment V8 calibration checks: 21/21 passed
- Assessment V9 final-calibration checks: 14/14 passed
- Vacancy deadline/status checks: 3/3 passed
- PHP syntax validation: 82 PHP files passed
- Assessment Insights Blade template compiled successfully and compiled PHP passed syntax validation

Full Artisan `view:cache` cannot run in the provided execution environment because its CLI renderer requires the PHP DOM extension (`DOMDocument`), which is not installed here. This is an environment limitation, not a project syntax failure; direct Blade compilation succeeded.
