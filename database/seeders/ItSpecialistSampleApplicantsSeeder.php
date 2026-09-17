<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\FormAnswer;
use App\Models\FormSubmission;
use App\Models\JobVacancy;
use App\Models\JobVacancyQualification;
use App\Models\Position;
use App\Services\AssessmentInsightService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ItSpecialistSampleApplicantsSeeder extends Seeder
{
    public function run(): void
    {
        $position = Position::query()
            ->where('name', 'IT SPECIALIST')
            ->with('department')
            ->first();

        if (! $position) {
            throw new RuntimeException('IT SPECIALIST position was not found. Run DepartmentSeeder and PositionSeeder first.');
        }

        $vacancy = JobVacancy::updateOrCreate(
            [
                'position_id' => $position->id,
                'title' => 'IT SPECIALIST - ASSESSMENT DEMO',
            ],
            [
                'slots' => 3,
                'employment_type' => 'Full-time',
                'description' => 'Sample IT Specialist vacancy for testing the automated Assessment Insights workflow.',
                'qualifications' => implode("\n", $this->qualificationTexts()),
                'salary_min' => null,
                'salary_max' => null,
                'opening_date' => today()->subDays(7),
                'closing_date' => today()->addDays(30),
                'status' => 'Open',
            ]
        );

        $this->seedQualifications($vacancy);

        $samples = $this->samples();
        $assessment = app(AssessmentInsightService::class);
        $templates = $assessment->templatesForVacancy($vacancy);

        if ($templates->count() < 2) {
            throw new RuntimeException('Official Application for Employment and Employment Questionnaire templates are required before sample applicants can be seeded.');
        }

        DB::transaction(function () use ($samples, $vacancy, $templates, $assessment) {
            foreach ($samples as $index => $sample) {
                $applicationAnswers = $sample['application'];
                $questionnaireAnswers = $sample['questionnaire'];

                $applicant = Applicant::updateOrCreate(
                    ['email' => $applicationAnswers['email_address']],
                    [
                        'first_name' => $applicationAnswers['first_name'],
                        'middle_name' => $applicationAnswers['middle_name'] ?: null,
                        'last_name' => $applicationAnswers['last_name'],
                        'mobile' => $applicationAnswers['cellphone_number'],
                        'address' => $applicationAnswers['present_address'],
                        'birthdate' => $applicationAnswers['birthdate'],
                        'gender' => $applicationAnswers['gender'],
                    ]
                );

                $application = Application::updateOrCreate(
                    [
                        'applicant_id' => $applicant->id,
                        'job_vacancy_id' => $vacancy->id,
                    ],
                    [
                        'reference_no' => 'RS8-DEMO-IT-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                        'status' => 'New Applicant',
                        'remarks' => 'Seeded sample applicant for IT Specialist Assessment Insights testing.',
                        'applied_at' => now()->subDays(3 - $index),
                    ]
                );

                foreach ($templates as $template) {
                    $submission = FormSubmission::updateOrCreate(
                        [
                            'application_id' => $application->id,
                            'form_template_id' => $template->id,
                        ],
                        [
                            'submitted_by' => null,
                            'submitted_at' => now()->subDays(3 - $index),
                        ]
                    );

                    $source = $template->type === 'application' ? $applicationAnswers : $questionnaireAnswers;
                    foreach ($template->fields as $field) {
                        if ($field->field_type === 'file' || ! array_key_exists($field->field_key, $source)) {
                            continue;
                        }

                        FormAnswer::updateOrCreate(
                            [
                                'form_submission_id' => $submission->id,
                                'form_field_id' => $field->id,
                            ],
                            ['value' => is_array($source[$field->field_key]) ? json_encode($source[$field->field_key]) : (string) $source[$field->field_key]]
                        );
                    }
                }

                $assessment->assess($application->fresh([
                    'applicant',
                    'vacancy.position.department',
                    'vacancy.qualificationsList',
                    'formSubmissions.template',
                    'formSubmissions.answers.field',
                ]));
            }
        });
    }

    private function qualificationTexts(): array
    {
        return [
            "Bachelor's degree in Information Technology, Computer Science, Information Systems, or a related field",
            'At least 1 year of experience in IT support, help desk, system administration, or a similar IT role',
            'Hands-on experience with Windows troubleshooting, hardware/software support, and basic networking',
            'Experience with Microsoft 365, Active Directory, DNS, DHCP, or similar administration tools is preferred',
            'Strong troubleshooting, documentation, and user-support skills',
        ];
    }

    private function seedQualifications(JobVacancy $vacancy): void
    {
        JobVacancyQualification::query()->where('job_vacancy_id', $vacancy->id)->delete();

        $rows = [
            ['education', 'required', null, null, 'high'],
            ['experience', 'required', 1, 'years', 'high'],
            ['skill', 'required', null, null, 'critical'],
            ['skill', 'preferred', null, null, 'medium'],
            ['skill', 'required', null, null, 'high'],
        ];

        foreach ($this->qualificationTexts() as $index => $text) {
            [$type, $level, $minimum, $unit, $importance] = $rows[$index];
            JobVacancyQualification::create([
                'job_vacancy_id' => $vacancy->id,
                'qualification_text' => $text,
                'qualification_type' => $type,
                'requirement_level' => $level,
                'minimum_value' => $minimum,
                'minimum_unit' => $unit,
                'evidence_source' => 'auto',
                'importance' => $importance,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    private function samples(): array
    {
        return [
            [
                'application' => array_merge($this->baseApplication([
                    'first_name' => 'Marco',
                    'middle_name' => 'Luis',
                    'last_name' => 'Dela Cruz',
                    'email_address' => 'sample.it.marco@rs8.test',
                    'cellphone_number' => '09170001001',
                    'present_address' => 'Quezon City, Metro Manila',
                    'birthdate' => '1998-04-17',
                    'gender' => 'Male',
                    'civil_status' => 'Single',
                    'availability' => 'Within 2 Weeks',
                    'current_employment_status' => 'Employed',
                    'notice_period' => '2 Weeks',
                    'college_school' => 'Technological University of the Philippines',
                    'college_address' => 'Manila',
                    'college_course' => 'Bachelor of Science in Information Technology',
                    'college_dates' => '2015 - 2019',
                    'work_experience_declaration' => 'Yes',
                    'company_1' => 'ByteBridge Solutions Inc.',
                    'company_address_1' => 'Quezon City',
                    'position_held_1' => 'IT Support Specialist',
                    'employment_dates_1' => '2023-01-15',
                    'employment_end_1' => '',
                    'currently_employed_1' => 'Yes',
                    'company_2' => 'PC Hub Services',
                    'company_address_2' => 'Manila',
                    'position_held_2' => 'Computer Technician',
                    'employment_dates_2' => '2021-06-01',
                    'employment_end_2' => '2022-12-31',
                    'currently_employed_2' => 'No',
                ]), []),
                'questionnaire' => $this->questionnaire([
                    'priority_growth_financial' => 'Growth is my priority because deeper technical skills and stronger responsibility improve my long-term stability as well.',
                    'company_contribution' => 'I can contribute structured troubleshooting, clear documentation, fast user support, and preventive maintenance practices.',
                    'one_year_outlook' => 'If hired, I want to be trusted with endpoint support, account administration, network troubleshooting, and IT documentation. If not hired, I will continue building system administration skills.',
                    'knowledge_about_rs8' => 'RS8 and Team RedSpeed operate in the motorcycle performance and aftermarket industry, supported by multiple business units and internal corporate teams.',
                    'work_life_preference' => 'I prefer sustainable work-life balance while remaining flexible during outages, deployments, or urgent operational needs.',
                    'role_motivation' => 'My current IT Support Specialist work directly matches end-user support, Windows troubleshooting, account administration, and basic network support.',
                    'role_relevant_skills' => 'Windows 10/11 troubleshooting, desktop and laptop hardware diagnostics, software installation, Active Directory user administration, Microsoft 365 support, TCP/IP troubleshooting, DNS and DHCP basics, ticket documentation, remote support, printer and LAN troubleshooting.',
                    'role_tools_systems' => 'Windows 10/11, Windows Server 2019, Active Directory, Microsoft 365 Admin Center, TCP/IP, DNS, DHCP, Remote Desktop, AnyDesk, Microsoft Excel',
                    'role_similar_project' => 'I handled a recurring office connectivity issue affecting about 20 users. I traced duplicate IP assignments, corrected DHCP reservations, documented the affected devices, and restored stable connectivity without replacing hardware.',
                    'role_problem_solving' => 'Several users intermittently lost access to shared folders and printers after a network change, while other workstations remained online.',
                    'role_problem_action' => 'I checked IP configuration, compared DHCP leases, tested gateway and DNS reachability, identified duplicate static addresses, corrected the affected configurations, and documented the final settings.',
                    'role_problem_result' => 'Connectivity was restored for all affected users the same day and the duplicate-IP incident did not recur during the following month.',
                    'role_strongest_requirement' => 'My strongest area is Windows and user support. I currently resolve desktop, account, printer, software, and basic LAN issues daily and document each resolution in our ticket log.',
                    'role_training_gap' => 'I would need deeper training on advanced firewall policy design and enterprise virtualization because my current role only gives me basic exposure to those areas.',
                ]),
            ],
            [
                'application' => $this->baseApplication([
                    'first_name' => 'Andrea',
                    'middle_name' => 'Mae',
                    'last_name' => 'Santos',
                    'email_address' => 'sample.it.andrea@rs8.test',
                    'cellphone_number' => '09170001002',
                    'present_address' => 'Mandaluyong City, Metro Manila',
                    'birthdate' => '2000-09-08',
                    'gender' => 'Female',
                    'civil_status' => 'Single',
                    'availability' => 'Immediately',
                    'current_employment_status' => 'Unemployed',
                    'notice_period' => 'Not Applicable',
                    'college_school' => 'Rizal Technological University',
                    'college_address' => 'Mandaluyong City',
                    'college_course' => 'Bachelor of Science in Computer Engineering',
                    'college_dates' => '2017 - 2021',
                    'work_experience_declaration' => 'Yes',
                    'company_1' => 'ServiceDesk PH Corp.',
                    'company_address_1' => 'Pasig City',
                    'position_held_1' => 'Help Desk Analyst',
                    'employment_dates_1' => '2024-01-08',
                    'employment_end_1' => '2025-08-30',
                    'currently_employed_1' => 'No',
                ]),
                'questionnaire' => $this->questionnaire([
                    'priority_growth_financial' => 'I value both, but I currently prioritize growth because stronger technical capability gives me better long-term opportunities.',
                    'company_contribution' => 'I can contribute patient user support, organized ticket handling, Windows troubleshooting, and Microsoft 365 assistance.',
                    'one_year_outlook' => 'If hired, I hope to handle more infrastructure responsibilities beyond help desk support. If not, I will continue studying networking and system administration.',
                    'knowledge_about_rs8' => 'I know RS8 is connected with motorcycle parts, performance products, racing, and business operations under Team RedSpeed.',
                    'work_life_preference' => 'Work-life balance helps me stay productive, although I understand IT sometimes requires support outside normal hours.',
                    'role_motivation' => 'My help desk experience is relevant because I supported users with Windows, Microsoft 365, password, printer, and connectivity issues.',
                    'role_relevant_skills' => 'Windows troubleshooting, Microsoft 365 user support, ticket handling, basic TCP/IP checks, remote desktop support, hardware replacement, printer troubleshooting, and user communication.',
                    'role_tools_systems' => 'Windows 10/11, Microsoft 365, Outlook, Teams, AnyDesk, Remote Desktop, basic TCP/IP commands',
                    'role_similar_project' => 'I helped prepare and deploy replacement laptops for a department migration. I installed approved applications, configured user profiles, transferred files, and checked access before turnover.',
                    'role_problem_solving' => 'A user could sign in to Windows but Outlook repeatedly asked for credentials and would not synchronize mail.',
                    'role_problem_action' => 'I checked the account status, verified network access, cleared cached credentials, recreated the Outlook profile, and tested synchronization with the user.',
                    'role_problem_result' => 'Email synchronization returned to normal and the user was able to continue work without escalation.',
                    'role_strongest_requirement' => 'User support and Windows troubleshooting are my strongest areas because those were my daily responsibilities for more than one year.',
                    'role_training_gap' => 'I need more hands-on practice with Active Directory administration, DNS, and DHCP because I normally escalated server-side changes to the infrastructure team.',
                ]),
            ],
            [
                'application' => $this->baseApplication([
                    'first_name' => 'Paolo',
                    'middle_name' => 'Miguel',
                    'last_name' => 'Reyes',
                    'email_address' => 'sample.it.paolo@rs8.test',
                    'cellphone_number' => '09170001003',
                    'present_address' => 'Cainta, Rizal',
                    'birthdate' => '2002-02-11',
                    'gender' => 'Male',
                    'civil_status' => 'Single',
                    'availability' => 'Within 1 Week',
                    'current_employment_status' => 'Unemployed',
                    'notice_period' => 'Not Applicable',
                    'vocational_school' => 'TESDA Training Center - Computer Systems Servicing',
                    'vocational_address' => 'Rizal',
                    'vocational_dates' => '2022',
                    'college_school' => '',
                    'college_address' => '',
                    'college_course' => '',
                    'college_dates' => '',
                    'work_experience_declaration' => 'Yes',
                    'company_1' => 'Local PC Repair Shop',
                    'company_address_1' => 'Cainta, Rizal',
                    'position_held_1' => 'Computer Technician',
                    'employment_dates_1' => '2025-01-06',
                    'employment_end_1' => '2025-08-29',
                    'currently_employed_1' => 'No',
                ]),
                'questionnaire' => $this->questionnaire([
                    'priority_growth_financial' => 'Growth is important to me because I am still early in my IT career and want more real workplace experience.',
                    'company_contribution' => 'I can contribute basic computer repair, Windows installation, hardware replacement, and willingness to learn company systems.',
                    'one_year_outlook' => 'If hired, I want to become more confident in networking and business IT support. If not, I will continue training and applying for entry-level IT jobs.',
                    'knowledge_about_rs8' => 'I know RS8 is a motorcycle parts and performance brand and Team RedSpeed is involved in motorsports and related businesses.',
                    'work_life_preference' => 'I prefer work-life balance but I can adjust when there is an urgent IT concern.',
                    'role_motivation' => 'I want to move from basic repair work into a broader IT support role where I can learn office systems and networking.',
                    'role_relevant_skills' => 'Desktop assembly, RAM and storage replacement, Windows installation, driver installation, basic malware cleanup, printer setup, and simple LAN cable checks.',
                    'role_tools_systems' => 'Windows 10, Windows 11, TeamViewer, basic PC diagnostic tools',
                    'role_similar_project' => 'I prepared several refurbished desktops for customer use by replacing failed drives, reinstalling Windows, installing drivers, and testing the units before release.',
                    'role_problem_solving' => 'A repaired desktop still restarted randomly even after Windows was reinstalled.',
                    'role_problem_action' => 'I checked temperatures, reseated the RAM, tested one memory stick at a time, and replaced the faulty module after the errors followed the same stick.',
                    'role_problem_result' => 'The computer completed stress testing without restarting after the RAM replacement.',
                    'role_strongest_requirement' => 'My strongest area is computer hardware and Windows installation because I handled those tasks frequently in the repair shop.',
                    'role_training_gap' => 'I need substantial training in Active Directory, Microsoft 365 administration, DNS, DHCP, and business network troubleshooting.',
                ]),
            ],
        ];
    }

    private function baseApplication(array $overrides): array
    {
        $base = [
            'availability' => 'To be discussed',
            'current_employment_status' => 'Unemployed',
            'notice_period' => 'Not Applicable',
            'applied_through' => 'Website',
            'referred_by' => '',
            'last_name' => '',
            'first_name' => '',
            'middle_name' => '',
            'nickname' => '',
            'present_address' => '',
            'birthdate' => '2000-01-01',
            'age' => '',
            'gender' => 'Prefer not to say',
            'civil_status' => 'Single',
            'religion' => '',
            'blood_type' => 'Prefer not to say',
            'cellphone_number' => '',
            'email_address' => '',
            'elementary_school' => 'Sample Elementary School',
            'elementary_address' => 'Philippines',
            'elementary_dates' => '2007 - 2012',
            'highschool_school' => 'Sample High School',
            'highschool_address' => 'Philippines',
            'highschool_dates' => '2012 - 2016',
            'vocational_school' => '',
            'vocational_address' => '',
            'vocational_dates' => '',
            'college_school' => '',
            'college_address' => '',
            'college_course' => '',
            'college_dates' => '',
            'work_experience_declaration' => 'No',
        ];

        for ($record = 1; $record <= 5; $record++) {
            $base["company_{$record}"] = '';
            $base["company_address_{$record}"] = '';
            $base["position_held_{$record}"] = '';
            $base["employment_dates_{$record}"] = '';
            $base["employment_end_{$record}"] = '';
            $base["currently_employed_{$record}"] = '';
        }

        $base = array_merge($base, [
            'reference_name_1' => '',
            'reference_title_1' => '',
            'reference_company_1' => '',
            'reference_phone_1' => '',
            'reference_name_2' => '',
            'reference_title_2' => '',
            'reference_company_2' => '',
            'reference_phone_2' => '',
        ]);

        return array_merge($base, $overrides);
    }

    private function questionnaire(array $answers): array
    {
        return $answers;
    }
}
