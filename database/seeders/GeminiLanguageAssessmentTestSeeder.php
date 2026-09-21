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
 * LOCALHOST ONLY: language-parity test dataset for the AI Evidence Interpreter.
 *
 * Goal:
 * - Equivalent English / Tagalog / Taglish answers should produce materially
 *   similar evidence interpretation and Assessment Insights scores.
 * - Weak/generic answers should remain weak in all three languages.
 * - Tool-name dumping should not be treated as demonstrated proficiency.
 *
 * Run:
 *   php artisan db:seed --class=GeminiLanguageAssessmentTestSeeder
 *
 * Creates 9 applicants under:
 *   IT SPECIALIST - GEMINI LANGUAGE TEST
 */
class GeminiLanguageAssessmentTestSeeder extends Seeder
{
    public function run(): void
    {
        app(StaticEmploymentFormService::class)->syncToDatabase();

        // Reuse the maintained IT demo so the language test uses the same
        // static fields, qualification rubric, employment structure and forms.
        $this->call(ItSpecialistSampleApplicantsSeeder::class);

        $sourceVacancy = JobVacancy::query()
            ->where('title', 'IT SPECIALIST - ASSESSMENT DEMO')
            ->with('qualificationsList')
            ->first();

        if (! $sourceVacancy) {
            throw new RuntimeException('IT Specialist demo vacancy was not created.');
        }

        $vacancy = JobVacancy::updateOrCreate(
            [
                'position_id' => $sourceVacancy->position_id,
                'title' => 'IT SPECIALIST - GEMINI LANGUAGE TEST',
            ],
            [
                'slots' => 9,
                'employment_type' => $sourceVacancy->employment_type,
                'description' => 'LOCALHOST ONLY - English/Tagalog/Taglish AI evidence interpretation parity test.',
                'qualifications' => $sourceVacancy->qualifications,
                'salary_min' => null,
                'salary_max' => null,
                'opening_date' => today()->subDay(),
                'closing_date' => today()->addDays(30),
                'status' => 'Open',
            ]
        );

        // Exact same vacancy qualification rubric as the normal IT demo.
        $vacancy->qualificationsList()->delete();
        foreach ($sourceVacancy->qualificationsList as $qualification) {
            $copy = $qualification->replicate();
            $copy->job_vacancy_id = $vacancy->id;
            $copy->save();
        }

        // Andrea is a useful baseline: qualifying degree + qualifying IT tenure.
        $source = Application::where('reference_no', 'RS8-DEMO-IT-002')->firstOrFail();

        $strongEnglish = [
            'role_motivation' => 'I am interested in IT support because I enjoy solving user problems, documenting fixes, and improving reliable access to systems.',
            'role_relevant_skills' => 'Windows troubleshooting, hardware and software support, basic networking, user support, documentation, Microsoft 365, Active Directory, DNS and DHCP.',
            'role_tools_systems' => 'Windows 10/11, Windows Server, Microsoft 365, Active Directory, DNS, DHCP, Remote Desktop, AnyDesk',
            'role_similar_project' => 'I supported office users by troubleshooting Windows workstations, resolving network access issues, configuring user access, validating printers and shared folders, and documenting the fixes.',
            'role_problem_solving' => 'Several users suddenly lost access to a shared folder after a network configuration change while other computers were still online.',
            'role_problem_action' => 'I checked the IP configuration, reviewed DHCP leases, tested DNS and gateway connectivity, found a duplicate IP address, corrected the affected configuration, renewed the connection, tested access again, and documented the fix.',
            'role_problem_result' => 'All affected users regained access to the shared folder, normal connectivity was restored, and the duplicate-IP issue did not recur during monitoring.',
            'role_strongest_requirement' => 'My strongest requirement is hands-on Windows troubleshooting, networking, documentation, and user support because I have used these skills in actual help desk work.',
            'role_training_gap' => 'I would like additional training in advanced server administration and enterprise automation.',
        ];

        $strongTagalog = [
            'role_motivation' => 'Interesado ako sa IT support dahil gusto kong mag-ayos ng problema ng users, mag-document ng mga ginawa kong solusyon, at siguraduhing maayos at stable ang access nila sa mga system.',
            'role_relevant_skills' => 'May karanasan ako sa Windows troubleshooting, hardware at software support, basic networking, user support, documentation, Microsoft 365, Active Directory, DNS at DHCP.',
            'role_tools_systems' => 'Windows 10/11, Windows Server, Microsoft 365, Active Directory, DNS, DHCP, Remote Desktop, AnyDesk',
            'role_similar_project' => 'Nag-support ako ng mga office user sa pag-troubleshoot ng Windows computers, pag-ayos ng network access, pag-configure ng user access, pag-test ng printers at shared folders, at pag-document ng mga naging solusyon.',
            'role_problem_solving' => 'Biglang hindi maka-access ang ilang users sa shared folder pagkatapos ng pagbabago sa network configuration habang nakakonekta pa nang maayos ang ibang computers.',
            'role_problem_action' => 'Chineck ko ang IP configuration, sinuri ko ang DHCP leases, tinest ko ang DNS at gateway connectivity, nakita kong may duplicate na IP address, inayos ko ang configuration ng affected device, ni-renew ko ang connection, nag-test ulit ako ng access, at dinocument ko ang ginawa kong fix.',
            'role_problem_result' => 'Nakabalik ang access ng lahat ng affected users sa shared folder, naging normal ulit ang connectivity, at hindi na naulit ang duplicate-IP issue habang mino-monitor ko ito.',
            'role_strongest_requirement' => 'Pinakamalakas kong qualification ang hands-on Windows troubleshooting, networking, documentation at user support dahil nagamit ko na ang mga ito sa aktwal na help desk work.',
            'role_training_gap' => 'Kailangan ko pa ng dagdag na training sa advanced server administration at enterprise automation.',
        ];

        $strongTaglish = [
            'role_motivation' => 'Interested ako sa IT support kasi gusto kong mag-solve ng user problems, mag-document ng fixes, at siguraduhin na reliable ang access nila sa systems.',
            'role_relevant_skills' => 'Experienced ako sa Windows troubleshooting, hardware/software support, basic networking, user support, documentation, Microsoft 365, Active Directory, DNS at DHCP.',
            'role_tools_systems' => 'Windows 10/11, Windows Server, Microsoft 365, Active Directory, DNS, DHCP, Remote Desktop, AnyDesk',
            'role_similar_project' => 'Nag-support ako ng office users by troubleshooting Windows workstations, fixing network access issues, configuring user access, checking printers and shared folders, then documenting the fixes.',
            'role_problem_solving' => 'May ilang users na biglang nawalan ng access sa shared folder after a network configuration change, pero okay pa yung ibang computers.',
            'role_problem_action' => 'Nag-check ako ng IP configuration and DHCP leases, nag-test ng DNS at gateway connectivity, nakita ko na duplicate pala yung IP address, inayos ko yung affected configuration, ni-renew yung connection, nag-test ulit ng access, then dinocument ko yung fix.',
            'role_problem_result' => 'Nakabalik lahat ng affected users sa shared folder, naging normal ulit yung connectivity, at hindi na naulit yung duplicate-IP issue habang mino-monitor.',
            'role_strongest_requirement' => 'Strongest requirement ko ang hands-on Windows troubleshooting, networking, documentation at user support kasi actual ko na itong nagamit sa help desk work.',
            'role_training_gap' => 'Need ko pa ng additional training sa advanced server administration and enterprise automation.',
        ];

        $weakEnglish = [
            'role_motivation' => 'I like IT.',
            'role_relevant_skills' => 'Windows support.',
            'role_tools_systems' => 'Windows.',
            'role_similar_project' => 'I fixed computers.',
            'role_problem_solving' => 'A computer did not work.',
            'role_problem_action' => 'I troubleshot it.',
            'role_problem_result' => 'It worked.',
            'role_strongest_requirement' => 'Support.',
            'role_training_gap' => 'Networking.',
        ];

        $weakTagalog = [
            'role_motivation' => 'Gusto ko ang IT.',
            'role_relevant_skills' => 'Windows support.',
            'role_tools_systems' => 'Windows.',
            'role_similar_project' => 'Nag-ayos ako ng computer.',
            'role_problem_solving' => 'Hindi gumana ang computer.',
            'role_problem_action' => 'Tinroubleshoot ko.',
            'role_problem_result' => 'Gumana na.',
            'role_strongest_requirement' => 'Support.',
            'role_training_gap' => 'Networking.',
        ];

        $weakTaglish = [
            'role_motivation' => 'Gusto ko yung IT.',
            'role_relevant_skills' => 'Windows support.',
            'role_tools_systems' => 'Windows.',
            'role_similar_project' => 'Nag-fix ako ng computer.',
            'role_problem_solving' => 'Hindi nag-work yung computer.',
            'role_problem_action' => 'Tinroubleshoot ko siya.',
            'role_problem_result' => 'Nag-work na.',
            'role_strongest_requirement' => 'Support.',
            'role_training_gap' => 'Networking.',
        ];

        $nameDropEnglish = [
            'role_motivation' => 'I want to work in IT.',
            'role_relevant_skills' => 'I know many IT technologies.',
            'role_tools_systems' => 'Windows Server, Active Directory, Microsoft 365, DNS, DHCP, Azure, VMware, Hyper-V, Linux, PowerShell, Intune, SCCM, Cisco, Fortinet',
            'role_similar_project' => 'I have seen these tools used in IT environments.',
            'role_problem_solving' => 'A user had a computer problem.',
            'role_problem_action' => 'I used the appropriate tools to solve it.',
            'role_problem_result' => 'The problem was solved.',
            'role_strongest_requirement' => 'I know many IT tools.',
            'role_training_gap' => 'None.',
        ];

        $nameDropTagalog = [
            'role_motivation' => 'Gusto kong magtrabaho sa IT.',
            'role_relevant_skills' => 'Marami akong alam na IT technologies.',
            'role_tools_systems' => 'Windows Server, Active Directory, Microsoft 365, DNS, DHCP, Azure, VMware, Hyper-V, Linux, PowerShell, Intune, SCCM, Cisco, Fortinet',
            'role_similar_project' => 'Nakita ko nang ginagamit ang mga tools na ito sa IT environment.',
            'role_problem_solving' => 'Nagkaroon ng problema ang computer ng isang user.',
            'role_problem_action' => 'Ginamit ko ang tamang tools para maayos ito.',
            'role_problem_result' => 'Naayos ang problema.',
            'role_strongest_requirement' => 'Marami akong alam na IT tools.',
            'role_training_gap' => 'Wala.',
        ];

        $nameDropTaglish = [
            'role_motivation' => 'Gusto kong mag-work sa IT.',
            'role_relevant_skills' => 'Familiar ako sa maraming IT technologies.',
            'role_tools_systems' => 'Windows Server, Active Directory, Microsoft 365, DNS, DHCP, Azure, VMware, Hyper-V, Linux, PowerShell, Intune, SCCM, Cisco, Fortinet',
            'role_similar_project' => 'Nakita ko na ginagamit itong mga tools sa IT environments.',
            'role_problem_solving' => 'Nagkaroon ng computer issue yung isang user.',
            'role_problem_action' => 'Ginamit ko yung appropriate tools para ma-solve yung issue.',
            'role_problem_result' => 'Na-solve yung problem.',
            'role_strongest_requirement' => 'Familiar ako sa maraming IT tools.',
            'role_training_gap' => 'None.',
        ];

        $cases = [
            ['Strong English', 'strong-en', 'EN-STRONG', $strongEnglish],
            ['Strong Tagalog', 'strong-tl', 'TL-STRONG', $strongTagalog],
            ['Strong Taglish', 'strong-tgl', 'TGL-STRONG', $strongTaglish],
            ['Weak English', 'weak-en', 'EN-WEAK', $weakEnglish],
            ['Weak Tagalog', 'weak-tl', 'TL-WEAK', $weakTagalog],
            ['Weak Taglish', 'weak-tgl', 'TGL-WEAK', $weakTaglish],
            ['Tool Name Drop English', 'tools-en', 'EN-TOOLS', $nameDropEnglish],
            ['Tool Name Drop Tagalog', 'tools-tl', 'TL-TOOLS', $nameDropTagalog],
            ['Tool Name Drop Taglish', 'tools-tgl', 'TGL-TOOLS', $nameDropTaglish],
        ];

        DB::transaction(function () use ($cases, $source, $vacancy) {
            foreach ($cases as $index => [$label, $slug, $code, $overrides]) {
                $this->cloneCase(
                    source: $source,
                    vacancy: $vacancy,
                    number: $index + 1,
                    label: $label,
                    slug: $slug,
                    code: $code,
                    overrides: $overrides,
                );
            }
        });

        $this->command?->newLine();
        $this->command?->info('Gemini language-parity test ready: 9 localhost-only applicants created.');
        $this->command?->line('Open vacancy: IT SPECIALIST - GEMINI LANGUAGE TEST');
        $this->command?->line('Compare these triplets:');
        $this->command?->line('  1) Strong English vs Strong Tagalog vs Strong Taglish');
        $this->command?->line('  2) Weak English vs Weak Tagalog vs Weak Taglish');
        $this->command?->line('  3) Tool Name Drop English vs Tagalog vs Taglish');
        $this->command?->warn('Expected: language alone should not create a material scoring advantage/disadvantage.');
    }

    private function cloneCase(
        Application $source,
        JobVacancy $vacancy,
        int $number,
        string $label,
        string $slug,
        string $code,
        array $overrides,
    ): void {
        $source->load('applicant', 'formSubmissions.answers.field');

        $email = "gemini.language.{$slug}@rs8.test";

        $applicant = Applicant::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => 'Gemini',
                'middle_name' => null,
                'last_name' => $label,
                'mobile' => '0999888' . str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'address' => 'Localhost Language Test Data',
                'birthdate' => $source->applicant->birthdate,
                'gender' => $source->applicant->gender,
            ]
        );

        $application = Application::updateOrCreate(
            [
                'applicant_id' => $applicant->id,
                'job_vacancy_id' => $vacancy->id,
            ],
            [
                'reference_no' => 'RS8-GEM-' . $code,
                'status' => 'New Applicant',
                'remarks' => "LOCALHOST GEMINI LANGUAGE TEST: {$label}",
                'applied_at' => now(),
            ]
        );

        // Rebuild submissions so rerunning remains deterministic.
        foreach ($application->formSubmissions()->with('answers')->get() as $oldSubmission) {
            $oldSubmission->answers()->delete();
            $oldSubmission->delete();
        }

        foreach ($source->formSubmissions as $sourceSubmission) {
            $submission = FormSubmission::create([
                'application_id' => $application->id,
                'form_template_id' => $sourceSubmission->form_template_id,
                'submitted_by' => null,
                'submitted_at' => now(),
            ]);

            foreach ($sourceSubmission->answers as $answer) {
                $fieldKey = $answer->field?->field_key;
                $value = ($fieldKey && array_key_exists($fieldKey, $overrides))
                    ? $overrides[$fieldKey]
                    : $answer->value;

                FormAnswer::create([
                    'form_submission_id' => $submission->id,
                    'form_field_id' => $answer->form_field_id,
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                ]);
            }
        }

        // Force the same normal assessment path used by the UI.
        app(AssessmentInsightService::class)->assess($application->fresh([
            'applicant',
            'vacancy.position.department',
            'vacancy.qualificationsList',
            'formSubmissions.template',
            'formSubmissions.answers.field',
        ]));
    }
}
