<?php

namespace Tests\Unit;

use App\Models\{JobVacancy, JobVacancyQualification, Position};
use App\Services\AssessmentInsightService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AssessmentV12ProductionReadinessTest extends TestCase
{
    private function call(string $method, array $args = []): mixed
    {
        return (new ReflectionMethod(AssessmentInsightService::class, $method))
            ->invokeArgs(new AssessmentInsightService(), $args);
    }

    private function vacancy(): JobVacancy
    {
        $rows = [
            ["Bachelor's degree in Information Technology, Computer Science, Information Systems, or a related field", 'education', 'required', 'high'],
            ['At least 1 year of experience in IT support, help desk, system administration, or a similar IT role', 'experience', 'required', 'high'],
            ['Hands-on experience with Windows troubleshooting, hardware/software support, and basic networking', 'skill', 'required', 'critical'],
            ['Experience with Microsoft 365, Active Directory, DNS, DHCP, or similar administration tools is preferred', 'skill', 'preferred', 'medium'],
            ['Strong troubleshooting, documentation, and user-support skills', 'skill', 'required', 'high'],
        ];

        $vacancy = new JobVacancy([
            'title' => 'IT Specialist',
            'qualifications' => implode("\n", array_column($rows, 0)),
        ]);
        $vacancy->setRelation('position', new Position(['name' => 'IT Specialist']));
        $vacancy->setRelation('qualificationsList', new Collection(array_map(
            fn ($row) => new JobVacancyQualification([
                'qualification_text' => $row[0],
                'qualification_type' => $row[1],
                'requirement_level' => $row[2],
                'importance' => $row[3],
                'is_active' => true,
            ]),
            $rows
        )));
        return $vacancy;
    }

    public function test_network_problem_evidence_gets_required_role_context_without_optional_leakage(): void
    {
        $labels = $this->call('problemRoleContextEvidenceLabels', [
            $this->vacancy(),
            'Several users lost access after a network change. I checked IP configuration, compared DHCP leases, tested the gateway, identified duplicate static addresses, corrected the configuration, documented the fix, and connectivity was restored.',
        ]);

        $this->assertContains('Basic networking', $labels);
        $this->assertContains('Documentation', $labels);
        $this->assertContains('Troubleshooting / problem solving', $labels);
        $this->assertSame(count($labels), count(array_unique($labels)));
    }

    public function test_preferred_only_tools_do_not_count_as_problem_solving_role_context(): void
    {
        $labels = $this->call('problemRoleContextEvidenceLabels', [
            $this->vacancy(),
            'I used Active Directory, DNS and DHCP.',
        ]);

        $this->assertNotContains('Active Directory', $labels);
        $this->assertNotContains('DNS', $labels);
        $this->assertNotContains('DHCP', $labels);
    }

    public function test_comparison_ui_separates_required_and_optional_qualification_counts(): void
    {
        $view = file_get_contents(resource_path('views/recruitment/assessment-insights/index.blade.php'));

        $this->assertStringContainsString('Required: ${metric.required_matched || 0}/${requiredTotal} met', $view);
        $this->assertStringContainsString('tie-breaker only; missing optional evidence does not reduce Role Fit', $view);
    }
}
