<?php

namespace Tests\Unit;

use App\Models\JobVacancy;
use App\Models\JobVacancyQualification;
use App\Models\Position;
use App\Services\AssessmentInsightService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AssessmentInsightServiceReliabilityTest extends TestCase
{
    private AssessmentInsightService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssessmentInsightService();
    }

    public function test_generic_system_word_does_not_satisfy_accounting_software_requirement(): void
    {
        $vacancy = $this->vacancy([
            'Must have experience using System (e.g., NetSuite, QuickBooks, or similar). Experience with NetSuite is an advantage.',
        ]);
        $requirement = $vacancy->qualificationsList->first();

        $result = $this->invoke('evaluateQualification', [
            $requirement,
            ['role_relevant_skills' => 'I use a system to organize accounting records.'],
            $vacancy,
        ]);

        $this->assertNotSame('matched', $result['status']);
        $this->assertContains($result['status'], ['not_evidenced', 'needs_verification']);
    }

    public function test_quickbooks_direct_evidence_matches_accounting_software_requirement(): void
    {
        $vacancy = $this->vacancy([
            'Must have experience using System (e.g., NetSuite, QuickBooks, or similar). Experience with NetSuite is an advantage.',
        ]);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['role_tools_systems' => 'I used QuickBooks for accounts payable and monthly bank reconciliation for 18 months.'],
            $vacancy,
        ]);

        $this->assertSame('matched', $result['status']);
        $this->assertStringContainsString('QuickBooks', $result['evidence']);
        $this->assertFalse($result['preferred_bonus_matched']);
    }

    public function test_xero_is_accepted_as_similar_accounting_erp_when_requirement_allows_similar(): void
    {
        $vacancy = $this->vacancy(['Must know NetSuite, QuickBooks, or similar accounting software.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['role_tools_systems' => 'Used Xero for invoicing, posting, and bank reconciliation.'],
            $vacancy,
        ]);

        $this->assertSame('matched', $result['status']);
        $this->assertStringContainsString('Xero', $result['evidence']);
    }

    public function test_domain_specific_pos_system_usage_can_match_without_catalog_entry(): void
    {
        $vacancy = $this->vacancy(['Must have experience using a POS system.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['role_tools_systems' => 'I used a POS system for sales transactions, receipts, and daily closing.'],
            $vacancy,
        ]);

        $this->assertSame('matched', $result['status']);
    }

    public function test_mysql_evidence_can_establish_generic_sql_requirement(): void
    {
        $vacancy = $this->vacancy(['Must have SQL experience.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['role_tools_systems' => 'I use MySQL for queries, joins, and reporting.'],
            $vacancy,
        ]);

        $this->assertSame('matched', $result['status']);
    }

    public function test_and_connected_named_tools_require_all_named_tools(): void
    {
        $vacancy = $this->vacancy(['Must know Microsoft Excel and QuickBooks.']);
        $requirement = $vacancy->qualificationsList->first();
        $partial = $this->invoke('evaluateQualification', [
            $requirement,
            ['role_tools_systems' => 'I use Microsoft Excel for reports.'],
            $vacancy,
        ]);
        $complete = $this->invoke('evaluateQualification', [
            $requirement,
            ['role_tools_systems' => 'I use Microsoft Excel and QuickBooks for reporting and reconciliation.'],
            $vacancy,
        ]);

        $this->assertNotSame('matched', $partial['status']);
        $this->assertSame('matched', $complete['status']);
    }

    public function test_training_gap_mention_never_becomes_positive_netsuite_evidence(): void
    {
        $vacancy = $this->vacancy(['Must have experience with NetSuite.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            [
                'role_relevant_skills' => 'Experienced in bookkeeping and bank reconciliation.',
                'role_training_gap' => 'I still need training in NetSuite.',
            ],
            $vacancy,
        ]);

        $this->assertNotSame('matched', $result['status']);
        $this->assertSame('needs_verification', $result['status']);
    }

    public function test_preferred_tool_does_not_replace_a_different_required_tool(): void
    {
        $vacancy = $this->vacancy(['Must know QuickBooks. NetSuite is an advantage.']);
        $requirement = $vacancy->qualificationsList->first();

        $netsuiteOnly = $this->invoke('evaluateQualification', [
            $requirement,
            ['role_tools_systems' => 'I use NetSuite for posting and reconciliation.'],
            $vacancy,
        ]);
        $quickbooks = $this->invoke('evaluateQualification', [
            $requirement,
            ['role_tools_systems' => 'I use QuickBooks for posting and reconciliation.'],
            $vacancy,
        ]);

        $this->assertNotSame('matched', $netsuiteOnly['status']);
        $this->assertTrue($netsuiteOnly['preferred_bonus_matched']);
        $this->assertSame('matched', $quickbooks['status']);
        $this->assertFalse($quickbooks['preferred_bonus_matched']);
    }

    public function test_purely_preferred_tool_qualification_is_still_assessable(): void
    {
        $vacancy = $this->vacancy(['NetSuite experience is preferred.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['role_tools_systems' => 'I used NetSuite for AP posting and reconciliation.'],
            $vacancy,
        ]);

        $this->assertSame('matched', $result['status']);
    }

    public function test_generic_accounting_software_accepts_named_accounting_tool_without_accepting_bare_system_word(): void
    {
        $vacancy = $this->vacancy(['Must have experience using accounting software. NetSuite is an advantage.']);
        $requirement = $vacancy->qualificationsList->first();

        $quickbooks = $this->invoke('evaluateQualification', [
            $requirement,
            ['role_tools_systems' => 'Used QuickBooks for invoicing and bank reconciliation.'],
            $vacancy,
        ]);
        $bareSystem = $this->invoke('evaluateQualification', [
            $requirement,
            ['role_relevant_skills' => 'I used a system for accounting records.'],
            $vacancy,
        ]);

        $this->assertSame('matched', $quickbooks['status']);
        $this->assertFalse($quickbooks['preferred_bonus_matched']);
        $this->assertNotSame('matched', $bareSystem['status']);
    }

    public function test_attention_to_detail_claim_alone_requires_verification(): void
    {
        $vacancy = $this->vacancy(['Strong attention to detail and accuracy']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['role_relevant_skills' => 'I have strong attention to detail and accuracy.'],
            $vacancy,
        ]);

        $this->assertSame('needs_verification', $result['status']);
    }

    public function test_attention_to_detail_can_be_supported_by_concrete_behavior(): void
    {
        $vacancy = $this->vacancy(['Strong attention to detail and accuracy']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['role_problem_solving' => 'I reviewed the records, compared the entries, found a discrepancy, and corrected it before submission.'],
            $vacancy,
        ]);

        $this->assertSame('matched', $result['status']);
    }

    public function test_bsit_does_not_match_accountancy_finance_degree_requirement(): void
    {
        $vacancy = $this->vacancy(["Bachelor's degree in Accountancy, Accounting Technology, Finance, or related field"]);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['college_course' => 'Bachelor of Science in Information Technology', 'college_completion_status' => 'Graduated / Degree Completed'],
            $vacancy,
        ]);

        $this->assertSame('not_matched', $result['status']);
    }

    public function test_unlisted_but_plausibly_related_degree_is_not_auto_failed(): void
    {
        $vacancy = $this->vacancy(["Bachelor's degree in Accountancy, Finance, or related field"]);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['college_course' => 'Bachelor of Science in Economics', 'college_completion_status' => 'Graduated / Degree Completed'],
            $vacancy,
        ]);

        $this->assertSame('needs_verification', $result['status']);
    }

    public function test_tagalog_problem_solving_actions_and_results_are_detected(): void
    {
        $text = 'Nireview ko ang records, kinumpara ko ang entries, at nalaman ko kung saan nanggaling ang problema bago ko inayos ang discrepancy.';
        $actions = $this->invoke('actionSignalCount', [$text]);
        $results = $this->invoke('resultSignalCount', [$text]);

        $this->assertGreaterThan(0, $actions);
        $this->assertGreaterThan(0, $results);
    }

    public function test_overlapping_employment_periods_are_counted_once(): void
    {
        $months = $this->invoke('mergedIntervalMonths', [[
            [Carbon::parse('2024-01-01'), Carbon::parse('2025-01-01')],
            [Carbon::parse('2024-06-01'), Carbon::parse('2024-12-01')],
        ]]);

        $this->assertSame(12, $months);
    }

    public function test_onsite_requirement_is_not_falsely_verified_by_start_availability(): void
    {
        $vacancy = $this->vacancy(['Must be willing to work onsite.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['availability' => 'Immediately', 'notice_period' => 'None / Can start immediately'],
            $vacancy,
        ]);

        $this->assertSame('not_evidenced', $result['status']);
    }

    public function test_start_timing_requirement_uses_actual_availability_window(): void
    {
        $vacancy = $this->vacancy(['Must be available to start within 2 weeks.']);
        $requirement = $vacancy->qualificationsList->first();

        $late = $this->invoke('evaluateQualification', [
            $requirement,
            ['availability' => 'Within 30 Days', 'notice_period' => '30 Days'],
            $vacancy,
        ]);
        $soon = $this->invoke('evaluateQualification', [
            $requirement,
            ['availability' => 'Within 1 Week', 'notice_period' => '1 Week'],
            $vacancy,
        ]);

        $this->assertSame('not_matched', $late['status']);
        $this->assertSame('matched', $soon['status']);
    }

    public function test_conflicting_availability_and_notice_period_uses_later_start_window(): void
    {
        $vacancy = $this->vacancy(['Must be available to start within 2 weeks.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['availability' => 'Within 1 Week', 'notice_period' => '30 Days'],
            $vacancy,
        ]);

        $this->assertSame('not_matched', $result['status']);
    }

    public function test_immediate_start_requires_both_availability_and_notice_to_allow_day_zero(): void
    {
        $vacancy = $this->vacancy(['Must be available to start immediately.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['availability' => 'Within 1 Week', 'notice_period' => 'None / Can start immediately'],
            $vacancy,
        ]);

        $this->assertSame('not_matched', $result['status']);
    }

    public function test_open_ended_more_than_thirty_days_is_not_assumed_to_fit_a_sixty_day_window(): void
    {
        $vacancy = $this->vacancy(['Must be available to start within 60 days.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['availability' => 'More than 30 Days', 'notice_period' => 'More than 30 Days'],
            $vacancy,
        ]);

        $this->assertSame('needs_verification', $result['status']);
    }

    public function test_nc_ii_identifier_matches_even_though_tokens_are_short(): void
    {
        $vacancy = $this->vacancy(['NC II certification is required.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['relevant_certifications' => 'TESDA National Certificate II (NC II) - Computer Systems Servicing', 'certification_type_1' => 'TESDA / National Certificate', 'certification_status_1' => 'Completed / Valid'],
            $vacancy,
        ]);

        $this->assertSame('matched', $result['status']);
    }

    public function test_unrelated_certificate_is_not_treated_as_proof_that_required_license_is_absent(): void
    {
        $vacancy = $this->vacancy(['CPA license is required.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['relevant_certifications' => 'Microsoft Office Specialist'],
            $vacancy,
        ]);

        $this->assertSame('not_evidenced', $result['status']);
    }

    public function test_required_language_wins_over_embedded_preferred_phrase(): void
    {
        $level = $this->invoke('inferRequirementLevel', [
            'Must know an accounting system. NetSuite is an advantage.',
        ]);

        $this->assertSame('required', $level);
    }

    public function test_critical_unverified_requirement_prevents_normal_fit_label(): void
    {
        $fit = $this->invoke('fit', [82.0, 72, [
            'score' => 100,
            'coverage' => 70,
            'required_not_matched_count' => 0,
            'required_not_verified_count' => 1,
            'critical_not_verified_count' => 1,
        ]]);

        $this->assertSame('Insufficient Evidence', $fit);
    }

    public function test_general_tool_claim_without_usage_requires_verification(): void
    {
        $vacancy = $this->vacancy(['Must have experience using QuickBooks.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['role_relevant_skills' => 'QuickBooks'],
            $vacancy,
        ]);

        $this->assertSame('needs_verification', $result['status']);
    }

    public function test_degree_wording_is_usable_without_a_separate_completion_question(): void
    {
        $vacancy = $this->vacancy(["Bachelor's degree in Accountancy is required"]);
        $requirement = $vacancy->qualificationsList->first();

        $missing = $this->invoke('evaluateQualification', [$requirement, ['college_course' => 'Bachelor of Science in Accountancy'], $vacancy]);
        $completed = $this->invoke('evaluateQualification', [$requirement, ['college_course' => 'Bachelor of Science in Accountancy', 'college_completion_status' => 'Graduated / Degree Completed'], $vacancy]);
        $undergrad = $this->invoke('evaluateQualification', [$requirement, ['college_course' => 'Bachelor of Science in Accountancy', 'college_completion_status' => 'Undergraduate / Not Completed'], $vacancy]);

        $this->assertSame('matched', $missing['status']);
        $this->assertSame('matched', $completed['status']);
        $this->assertSame('not_matched', $undergrad['status']);
    }

    public function test_removed_onsite_question_is_not_inferred_from_other_readiness_data(): void
    {
        $vacancy = $this->vacancy(['Must be willing to work onsite.']);
        $requirement = $vacancy->qualificationsList->first();
        $result = $this->invoke('evaluateQualification', [$requirement, [
            'availability' => 'Immediately',
            'current_employment_status' => 'Unemployed',
            'notice_period' => 'Not Applicable',
        ], $vacancy]);

        $this->assertSame('not_evidenced', $result['status']);
    }

    public function test_expired_required_license_is_not_treated_as_valid(): void
    {
        $vacancy = $this->vacancy(['CPA license is required.']);
        $requirement = $vacancy->qualificationsList->first();
        $expired = $this->invoke('evaluateQualification', [$requirement, [
            'professional_qualifications' => 'Certified Public Accountant (CPA)',
            'professional_qualification_type_1' => 'Professional License',
            'professional_qualification_status_1' => 'Expired',
        ], $vacancy]);
        $active = $this->invoke('evaluateQualification', [$requirement, [
            'professional_qualifications' => 'Certified Public Accountant (CPA)',
            'professional_qualification_type_1' => 'Professional License',
            'professional_qualification_status_1' => 'Active / Valid',
        ], $vacancy]);

        $this->assertSame('not_matched', $expired['status']);
        $this->assertSame('matched', $active['status']);
    }

    public function test_explicit_no_work_experience_is_a_real_gap_not_missing_evidence(): void
    {
        $vacancy = $this->vacancy(['At least 1 year of accounting experience is required.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            ['work_experience_declaration' => 'No'],
            $vacancy,
        ]);

        $this->assertSame('not_matched', $result['status']);
    }

    public function test_explicit_no_professional_credential_can_fail_required_license(): void
    {
        $vacancy = $this->vacancy(['CPA license is required.']);
        $result = $this->invoke('evaluateQualification', [
            $vacancy->qualificationsList->first(),
            [
                'professional_credential_declaration' => 'No',
                'certification_training_declaration' => 'No',
            ],
            $vacancy,
        ]);

        $this->assertSame('not_matched', $result['status']);
    }


    public function test_natural_outcome_language_is_recognized_as_result_evidence(): void
    {
        $marco = 'Connectivity was restored for all affected users the same day and the duplicate-IP incident did not recur during the following month.';
        $andrea = 'Email synchronization returned to normal and the user was able to continue work without escalation.';

        $this->assertGreaterThanOrEqual(2, $this->invoke('resultSignalCount', [$marco]));
        $this->assertGreaterThanOrEqual(2, $this->invoke('resultSignalCount', [$andrea]));
        $this->assertGreaterThan(0, $this->invoke('resultOutcomeContextCount', [$marco]));
        $this->assertGreaterThan(0, $this->invoke('resultOutcomeContextCount', [$andrea]));
    }

    public function test_software_versions_do_not_count_as_measurable_evidence(): void
    {
        $this->assertSame(0, $this->invoke('measurableEvidenceCount', ['Windows 11, Microsoft 365, Windows Server 2019']));
        $this->assertGreaterThan(0, $this->invoke('measurableEvidenceCount', ['Restored access for 20 users within 2 hours']));
    }

    public function test_preferred_qualification_does_not_reduce_required_baseline_score(): void
    {
        $vacancy = new JobVacancy([
            'title' => 'IT Specialist',
            'description' => 'Provide IT support.',
            'qualifications' => "Bachelor's degree in Information Technology\nMicrosoft 365 experience is preferred",
        ]);
        $vacancy->setRelation('position', new Position(['name' => 'IT Specialist']));
        $vacancy->setRelation('qualificationsList', new EloquentCollection([
            new JobVacancyQualification([
                'qualification_text' => "Bachelor's degree in Information Technology",
                'qualification_type' => 'education',
                'requirement_level' => 'required',
                'importance' => 'high',
                'sort_order' => 1,
                'is_active' => true,
            ]),
            new JobVacancyQualification([
                'qualification_text' => 'Microsoft 365 experience is preferred',
                'qualification_type' => 'skill',
                'requirement_level' => 'preferred',
                'importance' => 'medium',
                'sort_order' => 2,
                'is_active' => true,
            ]),
        ]));

        $requiredOnly = $this->invoke('qualificationMatchAssessment', [$vacancy, [
            'college_course' => 'Bachelor of Science in Information Technology',
        ]]);
        $withPreferred = $this->invoke('qualificationMatchAssessment', [$vacancy, [
            'college_course' => 'Bachelor of Science in Information Technology',
            'role_tools_systems' => 'I used Microsoft 365 Admin Center for account support.',
        ]]);

        $this->assertSame(100, $requiredOnly['score']);
        $this->assertSame(0, $requiredOnly['preferred_matched_count']);
        $this->assertSame(100, $withPreferred['score']);
        $this->assertSame(1, $withPreferred['preferred_matched_count']);
    }

    private function vacancy(array $qualificationTexts): JobVacancy
    {
        $vacancy = new JobVacancy([
            'title' => 'Accounting Associate',
            'description' => 'Handle accounting records, reconciliation, and financial documentation.',
            'qualifications' => implode("\n", $qualificationTexts),
        ]);
        $vacancy->setRelation('position', new Position(['name' => 'Accounting Associate']));

        $rows = [];
        foreach ($qualificationTexts as $index => $text) {
            $rows[] = new JobVacancyQualification([
                'qualification_text' => $text,
                'qualification_type' => 'auto',
                'requirement_level' => 'required',
                'importance' => 'high',
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
        $vacancy->setRelation('qualificationsList', new EloquentCollection($rows));

        return $vacancy;
    }

    private function invoke(string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($this->service, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($this->service, $arguments);
    }
}
