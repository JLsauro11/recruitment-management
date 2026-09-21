<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\FormAnswer;
use App\Models\FormSubmission;
use App\Models\JobVacancy;
use App\Services\AssessmentInsightService;
use App\Services\StaticEmploymentFormService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * LOCALHOST ONLY: adversarial/calibration dataset for Assessment Insights.
 *
 * Run:
 *   php artisan db:seed --class=AssessmentInsightsStressTestSeeder
 *
 * Creates 10 applicants under IT SPECIALIST - ASSESSMENT STRESS TEST.
 * It intentionally includes edge cases: minimum-qualified, unrelated tenure,
 * polished writing with weak qualifications, short answers, tool name-dropping,
 * education gap, contradictory claims, missing evidence, and identical twins.
 */
class AssessmentInsightsStressTestSeeder extends Seeder
{
    public function run(): void
    {
        app(StaticEmploymentFormService::class)->syncToDatabase();

        // Reuse the maintained IT demo to guarantee compatible static fields,
        // qualification records, and realistic source submissions.
        $this->call(ItSpecialistSampleApplicantsSeeder::class);

        $sourceVacancy = JobVacancy::query()
            ->where('title', 'IT SPECIALIST - ASSESSMENT DEMO')
            ->with('qualificationsList')
            ->first();

        if (! $sourceVacancy) {
            throw new RuntimeException('IT Specialist demo vacancy was not created.');
        }

        $vacancy = JobVacancy::updateOrCreate(
            ['position_id' => $sourceVacancy->position_id, 'title' => 'IT SPECIALIST - ASSESSMENT STRESS TEST'],
            [
                'slots' => 10,
                'employment_type' => $sourceVacancy->employment_type,
                'description' => 'LOCALHOST ONLY - adversarial calibration cases for Assessment Insights.',
                'qualifications' => $sourceVacancy->qualifications,
                'salary_min' => null,
                'salary_max' => null,
                'opening_date' => today()->subDay(),
                'closing_date' => today()->addDays(30),
                'status' => 'Open',
            ]
        );

        // Keep the exact same qualification rubric as the normal IT demo.
        $vacancy->qualificationsList()->delete();
        foreach ($sourceVacancy->qualificationsList as $q) {
            $copy = $q->replicate();
            $copy->job_vacancy_id = $vacancy->id;
            $copy->save();
        }

        $marco = Application::where('reference_no', 'RS8-DEMO-IT-001')->firstOrFail();
        $andrea = Application::where('reference_no', 'RS8-DEMO-IT-002')->firstOrFail();
        $paolo = Application::where('reference_no', 'RS8-DEMO-IT-003')->firstOrFail();

        $cases = [
            ['Ideal Candidate', 'ideal', $marco, []],
            ['Minimum Qualified', 'minimum', $andrea, [
                'employment_dates_1' => today()->subYear()->format('Y-m-d'),
                'employment_end_1' => today()->format('Y-m-d'),
                'currently_employed_1' => 'No',
            ]],
            ['Experienced Unrelated', 'unrelated', $marco, [
                'position_held_1' => 'Sales Associate', 'position_held_2' => 'Administrative Assistant',
                'role_relevant_skills' => 'Customer service, sales reporting, inventory encoding, and office administration.',
                'role_tools_systems' => 'Microsoft Excel, Google Sheets, POS system',
                'role_similar_project' => 'I prepared monthly sales reports and coordinated inventory counts for the branch.',
            ]],
            ['Great Writer Weak Qualifications', 'writer', $paolo, [
                'role_problem_solving' => 'A department experienced intermittent network access and several users could not reach shared resources after a configuration change.',
                'role_problem_action' => 'I documented symptoms, isolated affected endpoints, compared network configurations, tested connectivity layer by layer, recorded findings, coordinated escalation, and verified each restored service with users.',
                'role_problem_result' => 'Service was restored in a controlled sequence, affected users confirmed access, and the documented findings were retained for future troubleshooting.',
                'role_motivation' => 'I am highly motivated to build a career in enterprise IT support and I communicate technical findings clearly and systematically.',
            ]],
            ['Qualified Short Answers', 'short', $andrea, [
                'role_motivation' => 'I like IT.', 'role_relevant_skills' => 'Windows support.',
                'role_tools_systems' => 'Windows.', 'role_similar_project' => 'I fixed computers.',
                'role_problem_solving' => 'Computer did not work.', 'role_problem_action' => 'I troubleshot it.',
                'role_problem_result' => 'It worked.', 'role_strongest_requirement' => 'Support.',
                'role_training_gap' => 'Networking.',
            ]],
            ['Tool Name Dropper', 'namedrop', $andrea, [
                'role_tools_systems' => 'Windows Server, Active Directory, Microsoft 365, Azure, DNS, DHCP, VMware, Hyper-V, Cisco, Fortinet, Linux, PowerShell, Intune, SCCM',
                'role_relevant_skills' => 'I am familiar with many IT tools and technologies.',
                'role_similar_project' => 'I have seen these tools used in IT environments.',
                'role_problem_action' => 'I used the appropriate tools to solve the issue.',
                'role_problem_result' => 'The issue was solved.',
            ]],
            ['Strong Practical Education Gap', 'edugap', $paolo, [
                'employment_dates_1' => today()->subYears(4)->format('Y-m-d'),
                'employment_end_1' => '', 'currently_employed_1' => 'Yes',
                'position_held_1' => 'IT Support Technician',
                'role_relevant_skills' => 'Windows troubleshooting, hardware support, user support, networking, documentation, Active Directory, DNS and DHCP.',
                'role_tools_systems' => 'Windows 10/11, Windows Server, Active Directory, Microsoft 365, DNS, DHCP, Remote Desktop',
                'role_similar_project' => 'I migrated office workstations, joined devices to the domain, configured user access, validated printers and shared folders, and documented deployment results.',
                'role_problem_solving' => 'Users lost access to shared resources after an IP addressing change.',
                'role_problem_action' => 'I compared DHCP leases, checked DNS resolution, corrected addressing conflicts, tested access, and documented the corrected configuration.',
                'role_problem_result' => 'All affected users regained stable access and the issue did not recur during monitoring.',
            ]],
            ['Contradicting Applicant', 'contradict', $andrea, [
                'employment_dates_1' => today()->subMonths(6)->format('Y-m-d'),
                'employment_end_1' => today()->format('Y-m-d'), 'currently_employed_1' => 'No',
                'role_motivation' => 'I have more than three years of professional IT support experience handling enterprise users every day.',
                'role_strongest_requirement' => 'My three years of professional IT support experience is my strongest qualification.',
            ]],
            ['Missing Evidence', 'missing', $andrea, [
                'role_motivation' => '', 'role_relevant_skills' => '', 'role_tools_systems' => '',
                'role_similar_project' => '', 'role_problem_solving' => '', 'role_problem_action' => '',
                'role_problem_result' => '', 'role_strongest_requirement' => '', 'role_training_gap' => '',
            ]],
            ['Exact Twin of Ideal', 'twin', $marco, []],
        ];

        DB::transaction(function () use ($cases, $vacancy) {
            foreach ($cases as $i => [$label, $slug, $source, $overrides]) {
                $this->cloneCase($source, $vacancy, $i + 1, $label, $slug, $overrides);
            }
        });

        $this->command?->newLine();
        $this->command?->info('Assessment Insights stress test ready: 10 localhost-only applicants created.');
        $this->command?->line('Open vacancy: IT SPECIALIST - ASSESSMENT STRESS TEST');
        $this->command?->warn('Expected invariants: required gaps must dominate; missing != failure; name-dropping should not equal demonstrated skill; exact twins must score identically.');
    }

    private function cloneCase(Application $source, JobVacancy $vacancy, int $number, string $label, string $slug, array $overrides): void
    {
        $source->load('applicant', 'formSubmissions.answers.field');
        $email = "stress.{$slug}@rs8.test";

        $applicant = Applicant::updateOrCreate(['email' => $email], [
            'first_name' => 'Stress', 'middle_name' => null, 'last_name' => $label,
            'mobile' => '0999000' . str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'address' => 'Localhost Test Data', 'birthdate' => $source->applicant->birthdate,
            'gender' => $source->applicant->gender,
        ]);

        $application = Application::updateOrCreate(
            ['applicant_id' => $applicant->id, 'job_vacancy_id' => $vacancy->id],
            [
                'reference_no' => 'RS8-STRESS-IT-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'status' => 'New Applicant', 'remarks' => "LOCALHOST STRESS TEST: {$label}", 'applied_at' => now(),
            ]
        );

        // Rebuild submissions so rerunning the seeder is deterministic.
        foreach ($application->formSubmissions()->with('answers')->get() as $old) {
            $old->answers()->delete();
            $old->delete();
        }

        foreach ($source->formSubmissions as $sourceSubmission) {
            $submission = FormSubmission::create([
                'application_id' => $application->id,
                'form_template_id' => $sourceSubmission->form_template_id,
                'submitted_by' => null,
                'submitted_at' => now(),
            ]);

            foreach ($sourceSubmission->answers as $answer) {
                $key = $answer->field?->field_key;
                $value = ($key && array_key_exists($key, $overrides)) ? $overrides[$key] : $answer->value;
                FormAnswer::create([
                    'form_submission_id' => $submission->id,
                    'form_field_id' => $answer->form_field_id,
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                ]);
            }
        }

        app(AssessmentInsightService::class)->assess($application->fresh([
            'applicant', 'vacancy.position.department', 'vacancy.qualificationsList',
            'formSubmissions.template', 'formSubmissions.answers.field',
        ]));
    }
}
