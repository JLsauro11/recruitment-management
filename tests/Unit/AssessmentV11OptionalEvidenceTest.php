<?php

namespace Tests\Unit;

use App\Services\AssessmentInsightService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AssessmentV11OptionalEvidenceTest extends TestCase
{
    private function optionalBreadth(string $text, int $metric, string $status = 'matched'): array
    {
        $method = new ReflectionMethod(AssessmentInsightService::class, 'optionalPreferenceEvidenceStrength');
        return $method->invoke(new AssessmentInsightService(), $text, ['metric' => $metric], $status);
    }

    public function test_named_optional_technology_breadth_is_literal_coverage(): void
    {
        $text = 'Experience with Microsoft 365, Active Directory, DNS, DHCP is preferred';

        [$oneOfFour, $oneSummary] = $this->optionalBreadth($text, 1);
        [$fourOfFour, $fourSummary] = $this->optionalBreadth($text, 4);

        $this->assertSame(25, $oneOfFour);
        $this->assertSame(100, $fourOfFour);
        $this->assertStringContainsString('1/4', $oneSummary);
        $this->assertStringContainsString('4/4', $fourSummary);
    }

    public function test_unverified_optional_evidence_gets_no_tie_break_advantage(): void
    {
        [$breadth, $summary] = $this->optionalBreadth(
            'Experience with Microsoft 365, Active Directory, DNS, DHCP is preferred',
            2,
            'needs_verification'
        );

        $this->assertSame(0, $breadth);
        $this->assertStringContainsString('needs verification', strtolower($summary));
    }

    public function test_comparison_ui_uses_exact_optional_evidence_summary_not_synthetic_depth_label(): void
    {
        $view = file_get_contents(resource_path('views/recruitment/assessment-insights/index.blade.php'));

        $this->assertStringContainsString('Optional evidence:', $view);
        $this->assertStringContainsString('broader directly evidenced optional coverage', $view);
        $this->assertStringNotContainsString('optional evidence depth', strtolower($view));
    }
}
