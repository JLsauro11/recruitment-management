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

/**
 * Cross-role Assessment Insights validation data.
 *
 * Creates two intentionally different vacancies with three applicants each:
 * - Accounting Associate: education/accounting experience/reconciliation/AP-AR/software/detail accuracy
 * - Inventory Control Officer: education/inventory experience/stock control/detail accuracy/problem solving/Excel
 *
 * This seeder is demo/test data only. DatabaseSeeder calls it only in local/testing.
 */
class AssessmentValidationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $assessment = app(AssessmentInsightService::class);

        foreach ($this->vacancyDefinitions() as $definition) {
            $position = Position::query()->where('name', $definition['position'])->first();
            if (! $position) {
                throw new RuntimeException("Position not found for assessment validation seeding: {$definition['position']}. Run DepartmentSeeder and PositionSeeder first.");
            }

            $vacancy = JobVacancy::updateOrCreate(
                [
                    'position_id' => $position->id,
                    'title' => $definition['title'],
                ],
                [
                    'slots' => 3,
                    'employment_type' => 'Full-time',
                    'description' => $definition['description'],
                    'qualifications' => implode("\n", array_column($definition['qualifications'], 'text')),
                    'salary_min' => null,
                    'salary_max' => null,
                    'opening_date' => today()->subDays(7),
                    'closing_date' => today()->addDays(45),
                    'status' => 'Open',
                ]
            );

            $this->seedQualifications($vacancy, $definition['qualifications']);
            $templates = $assessment->templatesForVacancy($vacancy);
            if ($templates->count() < 2) {
                throw new RuntimeException('Official Application for Employment and Employment Questionnaire templates are required before assessment validation applicants can be seeded.');
            }

            DB::transaction(function () use ($definition, $vacancy, $templates, $assessment) {
                foreach ($definition['samples'] as $index => $sample) {
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
                            'reference_no' => 'RS8-VAL-' . $definition['code'] . '-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                            'status' => 'New Applicant',
                            'remarks' => 'Seeded cross-role applicant for Assessment Insights validation.',
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
    }

    /** @param array<int,array<string,mixed>> $qualifications */
    private function seedQualifications(JobVacancy $vacancy, array $qualifications): void
    {
        JobVacancyQualification::query()->where('job_vacancy_id', $vacancy->id)->delete();

        foreach ($qualifications as $index => $row) {
            JobVacancyQualification::create([
                'job_vacancy_id' => $vacancy->id,
                'qualification_text' => $row['text'],
                'qualification_type' => $row['type'],
                'requirement_level' => $row['level'],
                'minimum_value' => $row['minimum'] ?? null,
                'minimum_unit' => $row['unit'] ?? null,
                'evidence_source' => 'auto',
                'importance' => $row['importance'],
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Kept as one deterministic definition so regression tests can validate the
     * assessment engine against multiple job families without touching a database.
     *
     * @return array<int,array<string,mixed>>
     */
    private function vacancyDefinitions(): array
    {
        return [
            [
                'code' => 'ACC',
                'position' => 'ACCOUNTING ASSOCIATE',
                'title' => 'ACCOUNTING ASSOCIATE - ASSESSMENT VALIDATION',
                'description' => 'Cross-role validation vacancy for automated accounting Assessment Insights.',
                'qualifications' => [
                    ['text' => "Bachelor's degree in Accountancy, Accounting Technology, Finance, Business, or a related field", 'type' => 'education', 'level' => 'required', 'importance' => 'high'],
                    ['text' => 'At least 1 year of experience in accounting, bookkeeping, accounts payable, accounts receivable, or a similar finance role', 'type' => 'experience', 'level' => 'required', 'importance' => 'high', 'minimum' => 1, 'unit' => 'years'],
                    ['text' => 'Hands-on experience with bank reconciliation is required', 'type' => 'skill', 'level' => 'required', 'importance' => 'critical'],
                    ['text' => 'Experience with journal entries or accounts payable/receivable is required', 'type' => 'skill', 'level' => 'required', 'importance' => 'high'],
                    ['text' => 'Strong attention to detail and accuracy is required', 'type' => 'skill', 'level' => 'required', 'importance' => 'high'],
                    ['text' => 'Experience with NetSuite, QuickBooks, Xero, SAP, or similar accounting software is preferred', 'type' => 'skill', 'level' => 'preferred', 'importance' => 'medium'],
                ],
                'samples' => $this->accountingSamples(),
            ],
            [
                'code' => 'INV',
                'position' => 'INVENTORY CONTROL OFFICER',
                'title' => 'INVENTORY CONTROL OFFICER - ASSESSMENT VALIDATION',
                'description' => 'Cross-role validation vacancy for automated inventory and warehouse Assessment Insights.',
                'qualifications' => [
                    ['text' => "Bachelor's degree in Business Administration, Logistics, Supply Chain, Industrial Engineering, or a related field", 'type' => 'education', 'level' => 'required', 'importance' => 'high'],
                    ['text' => 'At least 1 year of experience in inventory control, warehouse, stock monitoring, or logistics', 'type' => 'experience', 'level' => 'required', 'importance' => 'high', 'minimum' => 1, 'unit' => 'years'],
                    ['text' => 'Hands-on experience with inventory control or stock monitoring is required', 'type' => 'skill', 'level' => 'required', 'importance' => 'critical'],
                    ['text' => 'Strong attention to detail and accuracy is required', 'type' => 'skill', 'level' => 'required', 'importance' => 'high'],
                    ['text' => 'Strong problem solving for inventory discrepancies is required', 'type' => 'skill', 'level' => 'required', 'importance' => 'high'],
                    ['text' => 'Experience with Microsoft Excel is preferred', 'type' => 'skill', 'level' => 'preferred', 'importance' => 'medium'],
                ],
                'samples' => $this->inventorySamples(),
            ],
        ];
    }

    /** @return array<int,array<string,array<string,string>>> */
    private function accountingSamples(): array
    {
        return [
            [
                'application' => $this->baseApplication([
                    'first_name' => 'Camille', 'middle_name' => 'Joy', 'last_name' => 'Reyes',
                    'email_address' => 'sample.acc.camille@rs8.test', 'cellphone_number' => '09170002001',
                    'present_address' => 'Pasig City, Metro Manila', 'birthdate' => '1997-05-22', 'gender' => 'Female',
                    'college_school' => 'Polytechnic University of the Philippines', 'college_address' => 'Manila',
                    'college_course' => 'Bachelor of Science in Accountancy', 'college_dates' => '2014 - 2018',
                    'work_experience_declaration' => 'Yes',
                    'company_1' => 'Northfield Distribution Corp.', 'company_address_1' => 'Pasig City',
                    'position_held_1' => 'Accounting Associate', 'employment_dates_1' => '2021-01-04', 'employment_end_1' => '2025-05-30', 'currently_employed_1' => 'No',
                    'company_2' => 'JMA Bookkeeping Services', 'company_address_2' => 'Mandaluyong City',
                    'position_held_2' => 'Bookkeeper', 'employment_dates_2' => '2019-07-01', 'employment_end_2' => '2020-12-18', 'currently_employed_2' => 'No',
                ]),
                'questionnaire' => $this->questionnaire([
                    'priority_growth_financial' => 'I prioritize professional growth while maintaining financial stability through consistent, accurate work.',
                    'company_contribution' => 'I can contribute disciplined reconciliation, clean supporting schedules, timely posting, and accurate accounting records.',
                    'one_year_outlook' => 'I want to own more month-end tasks and improve process controls while continuing to strengthen ERP skills.',
                    'knowledge_about_rs8' => 'RS8 operates motorcycle-parts and performance businesses with corporate support teams that need accurate financial records.',
                    'work_life_preference' => 'I value sustainable work-life balance and can adjust during month-end closing when needed.',
                    'role_motivation' => 'My accounting background directly matches the role because I handled reconciliation, journal entries, payables, receivables, and month-end schedules.',
                    'role_relevant_skills' => 'Bank reconciliation, accounts payable, accounts receivable, journal entries, general ledger review, financial record checking, supporting schedules, variance checking, and month-end close support.',
                    'role_tools_systems' => 'QuickBooks, NetSuite, Microsoft Excel, Google Sheets',
                    'role_similar_project' => 'I cleaned a three-month bank reconciliation backlog by matching bank transactions to ledger entries, identifying duplicate postings, preparing adjusting entries, and documenting unresolved items for approval.',
                    'role_problem_solving' => 'The cash ledger did not match the bank statement during month-end and several transactions appeared duplicated or missing.',
                    'role_problem_action' => 'I reconciled the statement line by line, compared deposit references and check numbers, traced duplicate journal entries, verified supporting documents, and prepared correcting entries for review.',
                    'role_problem_result' => 'The reconciliation difference was reduced to zero before closing and the duplicate-posting issue was documented so the team could prevent the same error the next month.',
                    'role_strongest_requirement' => 'Bank reconciliation and accuracy are my strongest areas because I performed reconciliations every month and reviewed supporting documents before posting adjustments.',
                    'role_training_gap' => 'I would need orientation on RS8-specific chart-of-accounts naming and approval flow, but I already use accounting ERP and spreadsheet tools.',
                ]),
            ],
            [
                'application' => $this->baseApplication([
                    'first_name' => 'Brian', 'middle_name' => 'Paul', 'last_name' => 'Mendoza',
                    'email_address' => 'sample.acc.brian@rs8.test', 'cellphone_number' => '09170002002',
                    'present_address' => 'Cainta, Rizal', 'birthdate' => '1999-11-14', 'gender' => 'Male',
                    'college_school' => 'University of Rizal System', 'college_address' => 'Rizal',
                    'college_course' => 'Bachelor of Science in Business Administration major in Financial Management', 'college_dates' => '2016 - 2020',
                    'work_experience_declaration' => 'Yes',
                    'company_1' => 'Metro Retail Services', 'company_address_1' => 'Quezon City',
                    'position_held_1' => 'Finance Assistant', 'employment_dates_1' => '2023-02-01', 'employment_end_1' => '2025-06-30', 'currently_employed_1' => 'No',
                ]),
                'questionnaire' => $this->questionnaire([
                    'priority_growth_financial' => 'I value growth because I want to deepen my accounting exposure while keeping stable long-term employment.',
                    'company_contribution' => 'I can contribute organized finance support, accurate encoding, reconciliation assistance, and good document control.',
                    'one_year_outlook' => 'I want to become more independent in reconciliations and journal-entry preparation.',
                    'knowledge_about_rs8' => 'RS8 has multiple motorcycle-related business units and needs reliable support functions including Finance.',
                    'work_life_preference' => 'I can support closing deadlines while keeping a sustainable schedule.',
                    'role_motivation' => 'My finance assistant work included bank reconciliation support, invoice checking, accounts payable monitoring, and spreadsheet schedules.',
                    'role_relevant_skills' => 'Bank reconciliation support, accounts payable monitoring, invoice checking, expense schedules, Microsoft Excel, basic journal-entry preparation, and document filing.',
                    'role_tools_systems' => 'Microsoft Excel, Google Sheets, basic QuickBooks exposure',
                    'role_similar_project' => 'I helped reconcile supplier payments by comparing payment schedules, bank references, invoices, and posted amounts before reporting unmatched items to the accountant.',
                    'role_problem_solving' => 'A supplier statement showed an outstanding invoice that our payment schedule marked as already paid.',
                    'role_problem_action' => 'I checked the invoice number, payment reference, bank transaction, and posting date, then found that the payment had been tagged to the wrong supplier account and coordinated the correction.',
                    'role_problem_result' => 'The supplier balance was corrected before the weekly payment run and the supporting schedule matched the accounting record.',
                    'role_strongest_requirement' => 'My strongest requirement is careful reconciliation support because I regularly compared invoices, bank references, and payment schedules.',
                    'role_training_gap' => 'I need more hands-on experience preparing complex adjusting entries and I have only basic QuickBooks exposure.',
                ]),
            ],
            [
                'application' => $this->baseApplication([
                    'first_name' => 'Nina', 'middle_name' => 'Rose', 'last_name' => 'Villanueva',
                    'email_address' => 'sample.acc.nina@rs8.test', 'cellphone_number' => '09170002003',
                    'present_address' => 'Marikina City, Metro Manila', 'birthdate' => '2002-03-03', 'gender' => 'Female',
                    'college_school' => 'AMA Computer College', 'college_address' => 'Quezon City',
                    'college_course' => 'Bachelor of Science in Information Technology', 'college_dates' => '2019 - 2023',
                    'work_experience_declaration' => 'Yes',
                    'company_1' => 'Neighborhood Retail Store', 'company_address_1' => 'Marikina City',
                    'position_held_1' => 'Administrative Cashier', 'employment_dates_1' => '2025-01-06', 'employment_end_1' => '2025-08-29', 'currently_employed_1' => 'No',
                ]),
                'questionnaire' => $this->questionnaire([
                    'priority_growth_financial' => 'I am looking for a role where I can learn a new business function and build stable experience.',
                    'company_contribution' => 'I can contribute careful data entry, receipt checking, and spreadsheet organization.',
                    'one_year_outlook' => 'I hope to learn bookkeeping and accounting procedures.',
                    'knowledge_about_rs8' => 'RS8 is a motorcycle parts and performance company.',
                    'work_life_preference' => 'I prefer a regular schedule and can adjust when needed.',
                    'role_motivation' => 'My current experience is mostly cashiering and administrative records, and I want to transition into accounting.',
                    'role_relevant_skills' => 'Cash handling, receipt encoding, basic spreadsheet data entry, filing, and daily sales totals.',
                    'role_tools_systems' => 'Microsoft Excel',
                    'role_similar_project' => 'I organized a month of sales receipts into a spreadsheet and checked totals against the cash log.',
                    'role_problem_solving' => 'A daily cash total was different from the sales summary.',
                    'role_problem_action' => 'I checked the receipts and encoded amounts again until I found one duplicated entry.',
                    'role_problem_result' => 'The daily total matched after the duplicated entry was removed.',
                    'role_strongest_requirement' => 'I am careful with receipts and data entry, but I do not yet have professional bank-reconciliation or journal-entry experience.',
                    'role_training_gap' => 'I need training in bank reconciliation, accounts payable/receivable, journal entries, and accounting software.',
                ]),
            ],
        ];
    }

    /** @return array<int,array<string,array<string,string>>> */
    private function inventorySamples(): array
    {
        return [
            [
                'application' => $this->baseApplication([
                    'first_name' => 'Jerome', 'middle_name' => 'Allan', 'last_name' => 'Navarro',
                    'email_address' => 'sample.inv.jerome@rs8.test', 'cellphone_number' => '09170003001',
                    'present_address' => 'Valenzuela City, Metro Manila', 'birthdate' => '1997-08-19', 'gender' => 'Male',
                    'college_school' => 'Pamantasan ng Lungsod ng Valenzuela', 'college_address' => 'Valenzuela City',
                    'college_course' => 'Bachelor of Science in Business Administration', 'college_dates' => '2014 - 2018',
                    'work_experience_declaration' => 'Yes',
                    'company_1' => 'Prime Parts Distribution', 'company_address_1' => 'Valenzuela City',
                    'position_held_1' => 'Inventory Control Officer', 'employment_dates_1' => '2021-02-01', 'employment_end_1' => '2025-05-31', 'currently_employed_1' => 'No',
                    'company_2' => 'Metro Warehouse Corp.', 'company_address_2' => 'Caloocan City',
                    'position_held_2' => 'Warehouse Administrative Staff', 'employment_dates_2' => '2019-07-01', 'employment_end_2' => '2021-01-15', 'currently_employed_2' => 'No',
                ]),
                'questionnaire' => $this->questionnaire([
                    'priority_growth_financial' => 'I value growth through stronger inventory controls and process ownership while maintaining stable employment.',
                    'company_contribution' => 'I can contribute disciplined cycle counts, stock reconciliation, receiving checks, discrepancy investigation, and clean inventory records.',
                    'one_year_outlook' => 'I want to improve inventory accuracy and help standardize stock-control procedures.',
                    'knowledge_about_rs8' => 'RS8 manages many motorcycle parts and product lines, so accurate stock visibility is important across business units.',
                    'work_life_preference' => 'I can adjust for scheduled counts and urgent inventory investigations when required.',
                    'role_motivation' => 'My inventory-control background directly covers stock monitoring, cycle counts, receiving, issuance, reconciliation, and discrepancy investigation.',
                    'role_relevant_skills' => 'Inventory control, stock monitoring, cycle counting, physical count reconciliation, receiving verification, stock issuance, discrepancy investigation, inventory records, and warehouse coordination.',
                    'role_tools_systems' => 'Microsoft Excel, Odoo, barcode scanner, inventory stock system',
                    'role_similar_project' => 'I led a cycle-count cleanup for fast-moving items, compared physical stock with system balances, traced posting and receiving errors, and prepared adjustment documentation for approval.',
                    'role_problem_solving' => 'A high-value part showed a negative system balance even though physical units were present in the rack.',
                    'role_problem_action' => 'I recounted the item, checked receiving documents and issuance records, compared transaction dates, traced a delayed receiving post, and documented the discrepancy before requesting the approved correction.',
                    'role_problem_result' => 'The inventory balance was corrected to the verified physical quantity and the team added a receiving-post checklist to prevent the same discrepancy.',
                    'role_strongest_requirement' => 'Inventory accuracy is my strongest area because I regularly reconcile physical counts with system records and investigate discrepancies before adjustments are posted.',
                    'role_training_gap' => 'I would need orientation on RS8 item codes and warehouse workflow, but I already have strong inventory-control and Excel experience.',
                ]),
            ],
            [
                'application' => $this->baseApplication([
                    'first_name' => 'Hannah', 'middle_name' => 'Grace', 'last_name' => 'Flores',
                    'email_address' => 'sample.inv.hannah@rs8.test', 'cellphone_number' => '09170003002',
                    'present_address' => 'Meycauayan, Bulacan', 'birthdate' => '2000-01-29', 'gender' => 'Female',
                    'college_school' => 'Bulacan State University', 'college_address' => 'Bulacan',
                    'college_course' => 'Bachelor of Science in Industrial Engineering', 'college_dates' => '2017 - 2021',
                    'work_experience_declaration' => 'Yes',
                    'company_1' => 'Central Logistics Hub', 'company_address_1' => 'Meycauayan, Bulacan',
                    'position_held_1' => 'Warehouse Staff', 'employment_dates_1' => '2023-08-01', 'employment_end_1' => '2025-07-31', 'currently_employed_1' => 'No',
                ]),
                'questionnaire' => $this->questionnaire([
                    'priority_growth_financial' => 'I want to grow from warehouse operations into inventory control while keeping stable work.',
                    'company_contribution' => 'I can contribute accurate receiving checks, stock counts, organized records, and coordination with warehouse staff.',
                    'one_year_outlook' => 'I want to become confident handling discrepancy reports and system inventory adjustments.',
                    'knowledge_about_rs8' => 'RS8 carries many motorcycle parts, so good receiving and inventory records help prevent stock issues.',
                    'work_life_preference' => 'I can support count schedules and peak warehouse activity.',
                    'role_motivation' => 'My warehouse experience includes receiving, stock counting, bin checking, issuance recording, and basic discrepancy reporting.',
                    'role_relevant_skills' => 'Stock monitoring, physical inventory count, receiving checks, issuance logs, bin location checking, inventory records, warehouse coordination, and Microsoft Excel.',
                    'role_tools_systems' => 'Microsoft Excel, Google Sheets, barcode scanner',
                    'role_similar_project' => 'I helped with a quarterly physical count by counting assigned bins, recording variances, recounting mismatches, and submitting discrepancy sheets to the inventory officer.',
                    'role_problem_solving' => 'A bin count was lower than the recorded stock during a physical inventory.',
                    'role_problem_action' => 'I recounted the bin, checked the adjacent location, reviewed the latest issuance log, and found that one issued item had not yet been written on the count sheet.',
                    'role_problem_result' => 'The variance was explained and the count sheet was corrected before the final inventory report was submitted.',
                    'role_strongest_requirement' => 'My strongest area is careful physical counting and receiving documentation because those were part of my daily warehouse duties.',
                    'role_training_gap' => 'I need more direct experience approving inventory adjustments and using a full ERP inventory module.',
                ]),
            ],
            [
                'application' => $this->baseApplication([
                    'first_name' => 'Kevin', 'middle_name' => 'James', 'last_name' => 'Ramos',
                    'email_address' => 'sample.inv.kevin@rs8.test', 'cellphone_number' => '09170003003',
                    'present_address' => 'Caloocan City, Metro Manila', 'birthdate' => '2003-06-10', 'gender' => 'Male',
                    'college_school' => '', 'college_address' => '', 'college_course' => '', 'college_dates' => '',
                    'vocational_school' => 'Local Skills Training Center', 'vocational_address' => 'Caloocan City', 'vocational_dates' => '2024',
                    'work_experience_declaration' => 'Yes',
                    'company_1' => 'Local Trading Warehouse', 'company_address_1' => 'Caloocan City',
                    'position_held_1' => 'Packer', 'employment_dates_1' => '2025-03-03', 'employment_end_1' => '2025-08-29', 'currently_employed_1' => 'No',
                ]),
                'questionnaire' => $this->questionnaire([
                    'priority_growth_financial' => 'I am looking for a role where I can learn more warehouse and inventory work.',
                    'company_contribution' => 'I can contribute careful packing, item checking, and willingness to learn stock systems.',
                    'one_year_outlook' => 'I want to build enough experience to handle stock monitoring and inventory records.',
                    'knowledge_about_rs8' => 'RS8 sells motorcycle parts and performance products.',
                    'work_life_preference' => 'I can adjust to warehouse schedules.',
                    'role_motivation' => 'My experience is mainly packing and item checking, and I want to move into inventory control.',
                    'role_relevant_skills' => 'Packing, item label checking, quantity checking, basic receiving assistance, and shelf organization.',
                    'role_tools_systems' => 'Basic barcode scanner use',
                    'role_similar_project' => 'I helped reorganize shelves by item code and checked package quantities before dispatch.',
                    'role_problem_solving' => 'A packed order was missing one item before dispatch.',
                    'role_problem_action' => 'I checked the packing list and shelf location, found the missing item, and completed the package.',
                    'role_problem_result' => 'The order was completed before dispatch.',
                    'role_strongest_requirement' => 'My strongest area is careful packing and quantity checking, but I do not yet have inventory-control officer experience.',
                    'role_training_gap' => 'I need training in inventory reconciliation, discrepancy investigation, stock systems, and Microsoft Excel.',
                ]),
            ],
        ];
    }

    private function baseApplication(array $overrides): array
    {
        $base = [
            'availability' => 'Immediately',
            'current_employment_status' => 'Unemployed',
            'notice_period' => 'Not Applicable',
            'applied_through' => 'Website',
            'referred_by' => '',
            'last_name' => '', 'first_name' => '', 'middle_name' => '', 'nickname' => '',
            'present_address' => '', 'birthdate' => '2000-01-01', 'age' => '',
            'gender' => 'Prefer not to say', 'civil_status' => 'Single', 'religion' => '', 'blood_type' => 'Prefer not to say',
            'cellphone_number' => '', 'email_address' => '',
            'elementary_school' => 'Sample Elementary School', 'elementary_address' => 'Philippines', 'elementary_dates' => '2007 - 2012',
            'highschool_school' => 'Sample High School', 'highschool_address' => 'Philippines', 'highschool_dates' => '2012 - 2016',
            'vocational_school' => '', 'vocational_address' => '', 'vocational_dates' => '',
            'college_school' => '', 'college_address' => '', 'college_course' => '', 'college_dates' => '',
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
            'reference_name_1' => '', 'reference_title_1' => '', 'reference_company_1' => '', 'reference_phone_1' => '',
            'reference_name_2' => '', 'reference_title_2' => '', 'reference_company_2' => '', 'reference_phone_2' => '',
        ]);

        return array_merge($base, $overrides);
    }

    private function questionnaire(array $answers): array
    {
        return $answers;
    }
}
