<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $application = DB::table('form_templates')->where('type', 'application')->where('name', 'Application for Employment')->first()
            ?? DB::table('form_templates')->where('type', 'application')->orderBy('sort_order')->first();
        if ($application) {
            $this->upsertField($application->id, 'availability', [
                'section' => 'Job Application Data',
                'label' => 'Availability to Start',
                'field_type' => 'select',
                'options' => ['Immediately', 'Within 1 Week', 'Within 2 Weeks', 'Within 30 Days', 'More than 30 Days', 'To be discussed'],
                'placeholder' => null,
                'is_required' => true,
                'sort_order' => 1,
                'width' => 4,
            ], $now);
            $this->upsertField($application->id, 'current_employment_status', [
                'section' => 'Job Application Data',
                'label' => 'Current Employment Status',
                'field_type' => 'select',
                'options' => ['Employed', 'Self-Employed', 'Unemployed', 'Student', 'Freelance / Project-Based'],
                'placeholder' => null,
                'is_required' => true,
                'sort_order' => 2,
                'width' => 4,
            ], $now);
            $this->upsertField($application->id, 'notice_period', [
                'section' => 'Job Application Data',
                'label' => 'Notice Period',
                'field_type' => 'select',
                'options' => ['None / Can start immediately', '1 Week', '2 Weeks', '30 Days', 'More than 30 Days', 'Not Applicable'],
                'placeholder' => null,
                'is_required' => false,
                'sort_order' => 3,
                'width' => 4,
            ], $now);
            $this->upsertField($application->id, 'applied_through', [
                'section' => 'Job Application Data', 'label' => 'Applied Through', 'field_type' => 'select',
                'options' => ['Walk In','Email','Facebook Page','Website','Job Fair','Employee Referral'],
                'placeholder' => null, 'is_required' => true, 'sort_order' => 4, 'width' => 4,
            ], $now);
            $this->upsertField($application->id, 'referred_by', [
                'section' => 'Job Application Data', 'label' => 'Referred By', 'field_type' => 'text',
                'options' => null, 'placeholder' => 'Name of referrer, if any', 'is_required' => false, 'sort_order' => 5, 'width' => 4,
            ], $now);

            $this->upsertField($application->id, 'relevant_certifications', [
                'section' => 'Educational Background',
                'label' => 'Relevant Certifications / Trainings',
                'field_type' => 'textarea',
                'options' => null,
                'placeholder' => 'List certifications, trainings, licenses, or seminars related to the role.',
                'is_required' => false,
                'sort_order' => 33,
                'width' => 12,
            ], $now);

            $employmentFields = [
                ['company_1','Company Name 1','text',4,34,null],
                ['company_address_1','Company Address 1','text',4,35,null],
                ['position_held_1','Position Held 1','text',4,36,null],
                ['employment_dates_1','Employment Start Date 1','date',3,37,null],
                ['employment_end_1','Employment End Date 1','date',3,38,null],
                ['currently_employed_1','Currently Employed Here?','select',3,39,['Yes','No']],
                ['reason_leaving_1','Reason for Leaving 1','text',3,40,null],
                ['duties_1','Key Duties / Achievements 1','textarea',12,41,null],
                ['company_2','Company Name 2','text',4,42,null],
                ['company_address_2','Company Address 2','text',4,43,null],
                ['position_held_2','Position Held 2','text',4,44,null],
                ['employment_dates_2','Employment Start Date 2','date',3,45,null],
                ['employment_end_2','Employment End Date 2','date',3,46,null],
                ['currently_employed_2','Currently Employed Here?','select',3,47,['Yes','No']],
                ['reason_leaving_2','Reason for Leaving 2','text',3,48,null],
                ['duties_2','Key Duties / Achievements 2','textarea',12,49,null],
            ];
            foreach ($employmentFields as [$key,$label,$type,$width,$order,$options]) {
                $this->upsertField($application->id, $key, [
                    'section' => 'Employment History', 'label' => $label, 'field_type' => $type,
                    'options' => $options, 'placeholder' => null, 'is_required' => false,
                    'sort_order' => $order, 'width' => $width,
                ], $now);
            }

            $referenceFields = [
                ['reference_name_1','Reference Name 1',3,50], ['reference_title_1','Position / Title 1',3,51],
                ['reference_company_1','Company 1',3,52], ['reference_phone_1','Contact Number 1',3,53],
                ['reference_name_2','Reference Name 2',3,54], ['reference_title_2','Position / Title 2',3,55],
                ['reference_company_2','Company 2',3,56], ['reference_phone_2','Contact Number 2',3,57],
            ];
            foreach ($referenceFields as [$key,$label,$width,$order]) {
                $this->upsertField($application->id, $key, [
                    'section' => 'References', 'label' => $label, 'field_type' => 'text',
                    'options' => null, 'placeholder' => null, 'is_required' => false,
                    'sort_order' => $order, 'width' => $width,
                ], $now);
            }
            $this->upsertField($application->id, 'resume', [
                'section' => 'Documents', 'label' => 'Resume / CV', 'field_type' => 'file',
                'options' => null, 'placeholder' => null, 'is_required' => false, 'sort_order' => 58, 'width' => 12,
            ], $now);
        }

        $questionnaire = DB::table('form_templates')->where('type', 'questionnaire')->where('name', 'Employment Questionnaire')->first()
            ?? DB::table('form_templates')->where('type', 'questionnaire')->orderBy('sort_order')->first();
        if ($questionnaire) {
            $existing = [
                ['priority_growth_financial','What is your priority in life: Growth or Financial Stability? Why?',1],
                ['company_contribution','If you were hired, what can you contribute to the company?',2],
                ['one_year_outlook','How do you see yourself one year from now if you are hired and if not?',3],
                ['knowledge_about_rs8','What do you know about RS8 and Team RedSpeed?',4],
                ['work_life_preference','Which do you prefer: Work-Life Balance or Work is Life? Why?',5],
            ];
            foreach ($existing as [$key,$label,$order]) {
                $this->upsertField($questionnaire->id, $key, [
                    'section' => 'Motivation & Culture Fit', 'label' => $label, 'field_type' => 'textarea',
                    'options' => null, 'placeholder' => 'Give a clear and specific answer.', 'is_required' => true,
                    'sort_order' => $order, 'width' => 12,
                ], $now);
            }

            $roleQuestions = [
                ['role_motivation','Why are you interested in this specific position, and which part of the role matches your background best?',6,'Explain your reason for applying and connect it to your experience or skills.'],
                ['role_relevant_skills','What skills, tools, software, systems, equipment, or knowledge do you have that are directly relevant to this position?',7,'Be specific. Include proficiency level or examples of actual use.'],
                ['role_similar_project','Describe the work task, project, responsibility, or achievement from your experience that is most similar to this role. What exactly did you do and what was the result?',8,'Use a real example and state your personal contribution and result.'],
                ['role_problem_solving','Describe a difficult work-related problem you personally solved. What was the situation, what action did you take, and what was the result?',9,'Use Situation - Action - Result when possible.'],
                ['role_strongest_requirement','Based on the job requirements, which requirement are you strongest in? Give a specific example that proves it.',10,'Mention the requirement and provide evidence.'],
                ['role_training_gap','Which requirement or responsibility of this position would you need the most training or support on?',11,'Answer honestly and explain how you plan to improve.'],
            ];
            foreach ($roleQuestions as [$key,$label,$order,$placeholder]) {
                $this->upsertField($questionnaire->id, $key, [
                    'section' => 'Role-Specific Assessment', 'label' => $label, 'field_type' => 'textarea',
                    'options' => null, 'placeholder' => $placeholder, 'is_required' => true,
                    'sort_order' => $order, 'width' => 12,
                ], $now);
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: existing applicant answers may reference these fields.
        // Rolling back should never delete recruitment evidence already submitted by applicants.
    }

    private function upsertField(int $templateId, string $key, array $data, $now): void
    {
        $payload = array_merge($data, [
            'options' => array_key_exists('options', $data) && is_array($data['options']) ? json_encode($data['options']) : ($data['options'] ?? null),
            'updated_at' => $now,
        ]);

        $existing = DB::table('form_fields')
            ->where('form_template_id', $templateId)
            ->where('field_key', $key)
            ->first();

        if ($existing) {
            DB::table('form_fields')->where('id', $existing->id)->update($payload);
            return;
        }

        DB::table('form_fields')->insert(array_merge($payload, [
            'form_template_id' => $templateId,
            'field_key' => $key,
            'created_at' => $now,
        ]));
    }
};
