<?php

namespace Tests\Unit;

use App\Models\{Application, FormAnswer, FormField, FormSubmission, FormTemplate, JobVacancy, JobVacancyQualification, Position};
use App\Services\AssessmentInsightService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AssessmentV10RegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-09 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function call(string $method, array $args = []): mixed
    {
        return (new ReflectionMethod(AssessmentInsightService::class, $method))
            ->invokeArgs(new AssessmentInsightService(), $args);
    }

    private function vacancy(string $text, string $level = 'required'): JobVacancy
    {
        $vacancy = new JobVacancy(['title' => 'IT Specialist', 'qualifications' => $text]);
        $vacancy->setRelation('position', new Position(['name' => 'IT Specialist']));
        $vacancy->setRelation('qualificationsList', new Collection([new JobVacancyQualification([
            'qualification_text' => $text, 'qualification_type' => 'auto',
            'requirement_level' => $level, 'importance' => 'high', 'is_active' => true,
        ])]));
        return $vacancy;
    }

    private function evaluate(JobVacancy $vacancy, array $answers): array
    {
        return $this->call('evaluateQualification', [$vacancy->qualificationsList->first(), $answers, $vacancy]);
    }

    public function test_invalid_and_relative_dates_are_not_silently_normalized(): void
    {
        foreach (['2026-02-30', '2026-13-01', 'tomorrow', '2026-00-12', ''] as $date) {
            $this->assertNull($this->call('parseDate', [$date]), $date);
        }
        $this->assertSame('2024-02-29', $this->call('parseDate', ['2024-02-29'])->toDateString());
        $this->assertSame('2024-02-01', $this->call('parseDate', ['2024-02'])->toDateString());
    }

    public function test_future_employment_does_not_generate_experience_points(): void
    {
        $vacancy = $this->vacancy('At least 2 years of IT experience');
        $answers = ['position_held_1' => 'IT Specialist', 'employment_dates_1' => '2027-01-01', 'employment_end_1' => '2030-01-01'];
        $this->assertSame('not_evidenced', $this->evaluate($vacancy, $answers)['status']);
        $this->assertNull($this->call('experienceAssessment', [$answers, $vacancy])['score']);
    }

    public function test_incomplete_dates_have_no_artificial_numeric_score(): void
    {
        $row = $this->call('experienceAssessment', [['position_held_1' => 'IT Specialist'], $this->vacancy('IT experience required')]);
        $this->assertNull($row['score']);
        $this->assertSame(0, $row['coverage']);
    }

    public function test_partial_history_is_uncertain_when_supported_months_are_below_minimum(): void
    {
        $v = $this->vacancy('At least 2 years of IT experience');
        $result = $this->evaluate($v, [
            'position_held_1' => 'IT Specialist', 'employment_dates_1' => '2025-01-01', 'employment_end_1' => '2026-01-01',
            'position_held_2' => 'IT Specialist', 'employment_dates_2' => '2020-01-01',
        ]);
        $this->assertSame('needs_verification', $result['status']);
    }

    public function test_experience_range_uses_lower_endpoint(): void
    {
        foreach (['2-3 years of IT experience', '2–3 years of IT experience', '2 to 3 years of IT experience', '2+ years of IT experience'] as $text) {
            $this->assertSame(24, $this->call('requiredExperienceMonths', [$this->vacancy($text)]), $text);
        }
    }

    public function test_optional_experience_minimum_does_not_change_supporting_baseline(): void
    {
        $this->assertNull($this->call('requiredExperienceMonths', [$this->vacancy('5 years of IT experience preferred', 'preferred')]));
    }

    public function test_course_wording_cannot_hide_explicit_undergraduate_status(): void
    {
        $v = $this->vacancy("Bachelor's degree in Information Technology required");
        $result = $this->evaluate($v, ['college_course' => 'BSIT - undergraduate']);
        $this->assertSame('not_matched', $result['status']);
    }

    public function test_associate_degree_does_not_satisfy_bachelor_requirement(): void
    {
        $v = $this->vacancy("Bachelor's degree in Information Technology required");
        $this->assertSame('not_matched', $this->evaluate($v, ['college_course' => 'Associate in Information Technology'])['status']);
    }

    public function test_optional_only_qualifications_do_not_create_a_required_score(): void
    {
        $v = $this->vacancy('Microsoft 365 experience preferred', 'preferred');
        $row = $this->call('qualificationMatchAssessment', [$v, ['role_tools_systems' => 'I used Microsoft 365 for user account administration.']]);
        $this->assertNull($row['score']);
        $this->assertSame(0, $row['required_total_count']);
        $this->assertSame('Insufficient Evidence', $this->call('fit', [90.0, 95, $row]));
    }

    public function test_disjoint_fractional_months_are_not_discarded_per_job(): void
    {
        $ranges = [
            [Carbon::parse('2024-01-01'), Carbon::parse('2024-01-20')],
            [Carbon::parse('2024-03-01'), Carbon::parse('2024-03-20')],
        ];
        $this->assertSame(1, $this->call('mergedIntervalMonths', [$ranges]));
    }

    public function test_custom_form_cannot_override_recruitment_evidence_keys(): void
    {
        $submission = new FormSubmission();
        $submission->setRelation('template', new FormTemplate(['name' => 'Custom Notes', 'type' => 'questionnaire']));
        $this->assertFalse($this->call('isEvidenceSubmission', [$submission]));
        $submission->setRelation('template', new FormTemplate(['name' => 'Employment Questionnaire', 'type' => 'questionnaire']));
        $this->assertTrue($this->call('isEvidenceSubmission', [$submission]));
    }

    public function test_fingerprint_changes_with_answer_vacancy_and_calendar_date(): void
    {
        $application = new Application();
        $vacancy = $this->vacancy('IT experience required');
        $application->setRelation('vacancy', $vacancy);
        $answer = new FormAnswer(['value' => 'Windows']);
        $answer->setRelation('field', new FormField(['field_key' => 'role_tools_systems', 'field_type' => 'textarea']));
        $submission = new FormSubmission();
        $submission->setRelation('template', new FormTemplate(['name' => 'Employment Questionnaire', 'type' => 'questionnaire']));
        $submission->setRelation('answers', new Collection([$answer]));
        $application->setRelation('formSubmissions', new Collection([$submission]));
        $service = new AssessmentInsightService();
        $first = $service->inputFingerprint($application);
        $this->assertSame($first, $service->inputFingerprint($application));
        $answer->value = 'Linux';
        $second = $service->inputFingerprint($application);
        $this->assertNotSame($first, $second);
        $vacancy->title = 'IT Manager';
        $third = $service->inputFingerprint($application);
        $this->assertNotSame($second, $third);
        Carbon::setTestNow(Carbon::parse('2026-09-10 12:00:00'));
        $this->assertNotSame($third, $service->inputFingerprint($application));
    }
}
