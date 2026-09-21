<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\AssessmentResult;
use App\Services\StaticEmploymentFormService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Complete, repeatable Assessment Insights test dataset.
 *
 * Creates 9 sample applicants across three different job families by reusing
 * the deterministic seeders already maintained for regression testing:
 * - IT Specialist: 3 applicants with strong / medium / developing evidence
 * - Accounting Associate: 3 applicants with varied accounting evidence
 * - Inventory Control Officer: 3 applicants with varied inventory evidence
 *
 * This seeder is intentionally NOT auto-run in production. Run it manually
 * only when you want visible test records in the Assessment Insights screen.
 */
class AssessmentInsightsTestSeeder extends Seeder
{
    public function run(): void
    {
        // Static questions are the single source of truth. Sync their stable DB
        // IDs first so test answers always use the exact field keys consumed by
        // AssessmentInsightService.
        app(StaticEmploymentFormService::class)->syncToDatabase();

        $this->command?->info('Seeding Assessment Insights test applicants...');

        DB::transaction(function () {
            $this->call([
                ItSpecialistSampleApplicantsSeeder::class,
                AssessmentValidationDemoSeeder::class,
            ]);
        });

        $testApplications = Application::query()
            ->where(function ($query) {
                $query->where('reference_no', 'like', 'RS8-DEMO-IT-%')
                    ->orWhere('reference_no', 'like', 'RS8-VAL-%');
            })
            ->count();

        $resultCount = AssessmentResult::query()
            ->whereHas('application', function ($query) {
                $query->where(function ($q) {
                    $q->where('reference_no', 'like', 'RS8-DEMO-IT-%')
                        ->orWhere('reference_no', 'like', 'RS8-VAL-%');
                });
            })
            ->count();

        $this->command?->newLine();
        $this->command?->info("Assessment Insights test data ready: {$testApplications} applications, {$resultCount} assessment result(s).");
        $this->command?->line('Open Recruitment > Assessment Insights and compare candidates inside the DEMO / ASSESSMENT VALIDATION vacancies.');
        $this->command?->warn('These are test records only. Do not use them for real hiring decisions.');
    }
}
