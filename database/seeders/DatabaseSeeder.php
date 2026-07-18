<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Department;
use App\Models\ExamResult;
use App\Models\Interview;
use App\Models\JobVacancy;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            /*
            |--------------------------------------------------------------------------
            | System Accounts
            |--------------------------------------------------------------------------
            */

            $admin = User::create([
                'name' => 'System Administrator',
                'email' => 'admin@rs8.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ]);

            $hr = User::create([
                'name' => 'HR Officer',
                'email' => 'hr@rs8.com',
                'password' => Hash::make('password'),
                'role' => 'hr',
                'status' => 'active',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Departments
            |--------------------------------------------------------------------------
            */

            $departments = collect([
                [
                    'name' => 'Human Resources',
                    'description' => 'Handles recruitment, employee relations, and company policies.',
                ],
                [
                    'name' => 'Sales',
                    'description' => 'Handles sales operations and client account management.',
                ],
                [
                    'name' => 'Marketing',
                    'description' => 'Handles advertising, promotions, and brand campaigns.',
                ],
                [
                    'name' => 'Accounting',
                    'description' => 'Handles financial records, payments, and reporting.',
                ],
                [
                    'name' => 'Warehouse',
                    'description' => 'Handles stocks, inventory, and warehouse operations.',
                ],
                [
                    'name' => 'Information Technology',
                    'description' => 'Handles systems, networks, and technical support.',
                ],
            ])->mapWithKeys(function (array $department) {
                $record = Department::create([
                    'name' => $department['name'],
                    'description' => $department['description'],
                    'status' => 'active',
                ]);

                return [$department['name'] => $record];
            });

            /*
            |--------------------------------------------------------------------------
            | Positions
            |--------------------------------------------------------------------------
            */

            $positions = collect([
                [
                    'department' => 'Sales',
                    'name' => 'Sales Executive',
                    'description' => 'Handles client accounts, sales inquiries, and collections.',
                ],
                [
                    'department' => 'Accounting',
                    'name' => 'Accounting Staff',
                    'description' => 'Assists in bookkeeping, payments, and financial records.',
                ],
                [
                    'department' => 'Warehouse',
                    'name' => 'Warehouse Staff',
                    'description' => 'Handles stock receiving, releasing, and inventory monitoring.',
                ],
                [
                    'department' => 'Marketing',
                    'name' => 'Marketing Assistant',
                    'description' => 'Supports content creation and marketing campaigns.',
                ],
                [
                    'department' => 'Information Technology',
                    'name' => 'IT Support',
                    'description' => 'Provides hardware, software, and network support.',
                ],
            ])->mapWithKeys(function (array $position) use ($departments) {
                $record = Position::create([
                    'department_id' => $departments[$position['department']]->id,
                    'name' => $position['name'],
                    'description' => $position['description'],
                    'status' => 'active',
                ]);

                return [$position['name'] => $record];
            });

            /*
            |--------------------------------------------------------------------------
            | Job Vacancies
            |--------------------------------------------------------------------------
            */

            $vacancies = collect([
                [
                    'position' => 'Sales Executive',
                    'title' => 'Sales Executive',
                    'slots' => 3,
                    'salary_min' => 18000,
                    'salary_max' => 25000,
                    'description' => 'Responsible for sales activities and client account management.',
                    'qualifications' => 'College graduate with good communication and negotiation skills.',
                ],
                [
                    'position' => 'Accounting Staff',
                    'title' => 'Accounting Staff',
                    'slots' => 2,
                    'salary_min' => 18000,
                    'salary_max' => 23000,
                    'description' => 'Supports financial recording and payment processing.',
                    'qualifications' => 'Graduate of Accounting, Finance, or a related course.',
                ],
                [
                    'position' => 'Warehouse Staff',
                    'title' => 'Warehouse Staff',
                    'slots' => 4,
                    'salary_min' => 15000,
                    'salary_max' => 19000,
                    'description' => 'Handles inventory, receiving, and stock releasing.',
                    'qualifications' => 'Physically fit and willing to work in warehouse operations.',
                ],
                [
                    'position' => 'Marketing Assistant',
                    'title' => 'Marketing Assistant',
                    'slots' => 1,
                    'salary_min' => 18000,
                    'salary_max' => 24000,
                    'description' => 'Assists in social media, campaigns, and promotional materials.',
                    'qualifications' => 'Creative and knowledgeable in social media and basic design.',
                ],
                [
                    'position' => 'IT Support',
                    'title' => 'IT Support',
                    'slots' => 1,
                    'salary_min' => 20000,
                    'salary_max' => 28000,
                    'description' => 'Provides system, network, and user technical support.',
                    'qualifications' => 'Graduate of IT, Computer Science, or related technical course.',
                ],
            ])->mapWithKeys(function (array $vacancy) use ($positions) {
                $record = JobVacancy::create([
                    'position_id' => $positions[$vacancy['position']]->id,
                    'title' => $vacancy['title'],
                    'slots' => $vacancy['slots'],
                    'employment_type' => 'Full-time',
                    'description' => $vacancy['description'],
                    'qualifications' => $vacancy['qualifications'],
                    'salary_min' => $vacancy['salary_min'],
                    'salary_max' => $vacancy['salary_max'],
                    'opening_date' => now()->subDays(10)->toDateString(),
                    'closing_date' => now()->addDays(30)->toDateString(),
                    'status' => 'Open',
                ]);

                return [$vacancy['position'] => $record];
            });

            /*
            |--------------------------------------------------------------------------
            | Applicants and Applicant User Accounts
            |--------------------------------------------------------------------------
            */

            $applicantData = [
                [
                    'first_name' => 'Juan',
                    'middle_name' => null,
                    'last_name' => 'Dela Cruz',
                    'email' => 'juan@example.com',
                    'mobile' => '09170000001',
                    'address' => 'Quezon City, Metro Manila',
                    'birthdate' => '1998-04-12',
                    'gender' => 'Male',
                    'vacancy' => 'Sales Executive',
                    'status' => 'For Initial Interview',
                    'applied_days_ago' => 4,
                ],
                [
                    'first_name' => 'Maria',
                    'middle_name' => 'Lopez',
                    'last_name' => 'Santos',
                    'email' => 'maria@example.com',
                    'mobile' => '09170000002',
                    'address' => 'Pasig City, Metro Manila',
                    'birthdate' => '1999-07-20',
                    'gender' => 'Female',
                    'vacancy' => 'Accounting Staff',
                    'status' => 'For Screening',
                    'applied_days_ago' => 3,
                ],
                [
                    'first_name' => 'Carlo',
                    'middle_name' => null,
                    'last_name' => 'Mendoza',
                    'email' => 'carlo@example.com',
                    'mobile' => '09170000003',
                    'address' => 'Cainta, Rizal',
                    'birthdate' => '1997-10-05',
                    'gender' => 'Male',
                    'vacancy' => 'Warehouse Staff',
                    'status' => 'For Examination',
                    'applied_days_ago' => 2,
                ],
                [
                    'first_name' => 'Angela',
                    'middle_name' => 'Reyes',
                    'last_name' => 'Garcia',
                    'email' => 'angela@example.com',
                    'mobile' => '09170000004',
                    'address' => 'Makati City, Metro Manila',
                    'birthdate' => '2000-01-18',
                    'gender' => 'Female',
                    'vacancy' => 'Marketing Assistant',
                    'status' => 'Hired',
                    'applied_days_ago' => 8,
                ],
                [
                    'first_name' => 'Mark',
                    'middle_name' => null,
                    'last_name' => 'Villanueva',
                    'email' => 'mark@example.com',
                    'mobile' => '09170000005',
                    'address' => 'Marikina City, Metro Manila',
                    'birthdate' => '1996-12-01',
                    'gender' => 'Male',
                    'vacancy' => 'IT Support',
                    'status' => 'For Final Interview',
                    'applied_days_ago' => 5,
                ],
            ];

            foreach ($applicantData as $index => $data) {
                $fullName = collect([
                    $data['first_name'],
                    $data['middle_name'],
                    $data['last_name'],
                ])->filter()->implode(' ');

                /*
                 * Bawat applicant ay may corresponding user account.
                 */
                $applicantUser = User::create([
                    'name' => $fullName,
                    'email' => $data['email'],
                    'password' => Hash::make('password'),
                    'role' => 'applicant',
                    'status' => 'active',
                ]);

                $applicant = Applicant::create([
                    'user_id' => $applicantUser->id,
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'mobile' => $data['mobile'],
                    'address' => $data['address'],
                    'birthdate' => $data['birthdate'],
                    'gender' => $data['gender'],
                    'resume_path' => null,
                ]);

                $application = Application::create([
                    'applicant_id' => $applicant->id,
                    'job_vacancy_id' => $vacancies[$data['vacancy']]->id,
                    'reference_no' => 'RS8-' . now()->year . '-' .
                        str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                    'status' => $data['status'],
                    'remarks' => null,
                    'applied_at' => now()->subDays($data['applied_days_ago']),
                ]);

                /*
                 * Base application history.
                 */
                $application->statusHistories()->create([
                    'status' => 'Application Submitted',
                    'remarks' => 'Application was submitted successfully.',
                    'updated_by' => $applicantUser->id,
                    'created_at' => $application->applied_at,
                    'updated_at' => $application->applied_at,
                ]);

                $this->seedApplicationProgress(
                    application: $application,
                    status: $data['status'],
                    adminId: $admin->id,
                    hrId: $hr->id
                );
            }
        });
    }

    private function seedApplicationProgress(
        Application $application,
        string $status,
        int $adminId,
        int $hrId
    ): void {
        $progressStages = [
            'For Screening' => [
                'For Screening',
            ],
            'For Initial Interview' => [
                'For Screening',
                'For Initial Interview',
            ],
            'For Examination' => [
                'For Screening',
                'For Initial Interview',
                'For Examination',
            ],
            'For Final Interview' => [
                'For Screening',
                'For Initial Interview',
                'For Examination',
                'For Final Interview',
            ],
            'For Job Offer' => [
                'For Screening',
                'For Initial Interview',
                'For Examination',
                'For Final Interview',
                'For Job Offer',
            ],
            'Hired' => [
                'For Screening',
                'For Initial Interview',
                'For Examination',
                'For Final Interview',
                'For Job Offer',
                'Hired',
            ],
            'Rejected' => [
                'For Screening',
                'Rejected',
            ],
        ];

        $stages = $progressStages[$status] ?? [];

        foreach ($stages as $stageIndex => $stage) {
            $application->statusHistories()->create([
                'status' => $stage,
                'remarks' => 'Application moved to ' . $stage . '.',
                'updated_by' => $stage === 'Hired' ? $adminId : $hrId,
                'created_at' => $application->applied_at
                    ->copy()
                    ->addDays($stageIndex + 1),
                'updated_at' => $application->applied_at
                    ->copy()
                    ->addDays($stageIndex + 1),
            ]);
        }

        if (in_array($status, [
            'For Initial Interview',
            'For Examination',
            'For Final Interview',
            'For Job Offer',
            'Hired',
        ], true)) {
            Interview::create([
                'application_id' => $application->id,
                'interviewer_id' => $hrId,
                'type' => 'Initial Interview',
                'scheduled_at' => $status === 'For Initial Interview'
                    ? now()->addDay()->setTime(10, 30)
                    : now()->subDays(3)->setTime(10, 30),
                'location' => 'HR Office',
                'meeting_link' => null,
                'status' => $status === 'For Initial Interview'
                    ? 'Scheduled'
                    : 'Completed',
                'remarks' => $status === 'For Initial Interview'
                    ? 'Applicant is scheduled for an initial interview.'
                    : 'Applicant completed the initial interview.',
            ]);
        }

        if (in_array($status, [
            'For Final Interview',
            'For Job Offer',
            'Hired',
        ], true)) {
            ExamResult::create([
                'application_id' => $application->id,
                'exam_type' => 'Aptitude Test',
                'score' => 85,
                'passing_score' => 75,
                'result' => 'Passed',
                'remarks' => 'Applicant passed the aptitude examination.',
            ]);

            Interview::create([
                'application_id' => $application->id,
                'interviewer_id' => $hrId,
                'type' => 'Final Interview',
                'scheduled_at' => $status === 'For Final Interview'
                    ? now()->addDays(2)->setTime(14, 0)
                    : now()->subDay()->setTime(14, 0),
                'location' => 'Conference Room',
                'meeting_link' => null,
                'status' => $status === 'For Final Interview'
                    ? 'Scheduled'
                    : 'Completed',
                'remarks' => $status === 'For Final Interview'
                    ? 'Applicant is scheduled for the final interview.'
                    : 'Applicant completed the final interview.',
            ]);
        }

        if ($status === 'For Examination') {
            ExamResult::create([
                'application_id' => $application->id,
                'exam_type' => 'Aptitude Test',
                'score' => 82,
                'passing_score' => 75,
                'result' => 'Passed',
                'remarks' => 'Applicant passed the aptitude examination.',
            ]);
        }
    }
}