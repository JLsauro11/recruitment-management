<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Single source of truth for the public employment application.
 *
 * Questions and field behavior are intentionally defined in code, not editable
 * from the recruitment UI. The database keeps stable template/field IDs only so
 * existing submissions, answers, reports, and Assessment Insights continue to
 * work without a destructive schema rewrite.
 */
class StaticEmploymentFormService
{
    public function definitions(): array
    {
        return [
            [
                'name' => 'Application for Employment',
                'type' => 'application',
                'description' => 'Official RedSpeed Motoworkz OPC employment application. This form is system-managed and its fields are defined in code.',
                'sort_order' => 1,
                'fields' => [
                    ['Job Application Data','Availability to Start','availability','select',true,4,['Immediately','Within 1 Week','Within 2 Weeks','Within 30 Days','More than 30 Days','To be discussed']],
                    ['Job Application Data','Current Employment Status','current_employment_status','select',true,4,['Employed','Self-Employed','Unemployed','Student','Freelance / Project-Based']],
                    ['Job Application Data','Notice Period','notice_period','select',false,4,['None / Can start immediately','1 Week','2 Weeks','30 Days','More than 30 Days','Not Applicable']],
                    ['Job Application Data','Applied Through','applied_through','select',true,6,['Walk In','Email','Facebook Page','Website','Job Fair','Employee Referral']],
                    ['Job Application Data','Referred By','referred_by','text',false,6],

                    ['Personal Information','Last Name','last_name','text',true,3],
                    ['Personal Information','First Name','first_name','text',true,3],
                    ['Personal Information','Middle Name','middle_name','text',false,3],
                    ['Personal Information','Nickname','nickname','text',false,3],
                    ['Personal Information','Address','present_address','textarea',true,12],
                    ['Personal Information','Birthdate','birthdate','date',true,3],
                    ['Personal Information','Age','age','number',false,2],
                    ['Personal Information','Gender','gender','select',true,2,['Male','Female','Prefer not to say']],
                    ['Personal Information','Civil Status','civil_status','select',true,2,['Single','Married','Widowed','Separated','With Partner']],
                    ['Personal Information','Religion','religion','text',false,2],
                    ['Personal Information','Blood Type','blood_type','select',false,3,['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown / Not Sure','Prefer not to say']],
                    ['Personal Information','Cellphone Number','cellphone_number','text',true,4],
                    ['Personal Information','Email Address','email_address','email',true,5],

                    ['Educational Background','Elementary School','elementary_school','text',false,4],
                    ['Educational Background','Elementary Address','elementary_address','text',false,4],
                    ['Educational Background','Elementary Inclusive Dates','elementary_dates','text',false,4],
                    ['Educational Background','High School','highschool_school','text',false,4],
                    ['Educational Background','High School Address','highschool_address','text',false,4],
                    ['Educational Background','High School Inclusive Dates','highschool_dates','text',false,4],
                    ['Educational Background','Vocational School / Course','vocational_school','text',false,4],
                    ['Educational Background','Vocational Address','vocational_address','text',false,4],
                    ['Educational Background','Vocational Inclusive Dates','vocational_dates','text',false,4],
                    ['Educational Background','College / University','college_school','text',false,3],
                    ['Educational Background','College Address','college_address','text',false,3],
                    ['Educational Background','Degree / Course','college_course','text',false,3],
                    ['Educational Background','College Inclusive Dates','college_dates','text',false,3],

                    ['Employment History','Do you have previous or current work experience?','work_experience_declaration','select',true,12,['Yes','No']],
                    ['Employment History','Company Name 1','company_1','text',false,4],
                    ['Employment History','Company Address 1','company_address_1','text',false,4],
                    ['Employment History','Position Held 1','position_held_1','text',false,4],
                    ['Employment History','Employment Start Date 1','employment_dates_1','date',false,4],
                    ['Employment History','Employment End Date 1','employment_end_1','date',false,4],
                    ['Employment History','Currently Employed Here? 1','currently_employed_1','select',false,4,['Yes','No']],
                    ['Employment History','Company Name 2','company_2','text',false,4],
                    ['Employment History','Company Address 2','company_address_2','text',false,4],
                    ['Employment History','Position Held 2','position_held_2','text',false,4],
                    ['Employment History','Employment Start Date 2','employment_dates_2','date',false,4],
                    ['Employment History','Employment End Date 2','employment_end_2','date',false,4],
                    ['Employment History','Currently Employed Here? 2','currently_employed_2','select',false,4,['Yes','No']],
                    ['Employment History','Company Name 3','company_3','text',false,4],
                    ['Employment History','Company Address 3','company_address_3','text',false,4],
                    ['Employment History','Position Held 3','position_held_3','text',false,4],
                    ['Employment History','Employment Start Date 3','employment_dates_3','date',false,4],
                    ['Employment History','Employment End Date 3','employment_end_3','date',false,4],
                    ['Employment History','Currently Employed Here? 3','currently_employed_3','select',false,4,['Yes','No']],
                    ['Employment History','Company Name 4','company_4','text',false,4],
                    ['Employment History','Company Address 4','company_address_4','text',false,4],
                    ['Employment History','Position Held 4','position_held_4','text',false,4],
                    ['Employment History','Employment Start Date 4','employment_dates_4','date',false,4],
                    ['Employment History','Employment End Date 4','employment_end_4','date',false,4],
                    ['Employment History','Currently Employed Here? 4','currently_employed_4','select',false,4,['Yes','No']],
                    ['Employment History','Company Name 5','company_5','text',false,4],
                    ['Employment History','Company Address 5','company_address_5','text',false,4],
                    ['Employment History','Position Held 5','position_held_5','text',false,4],
                    ['Employment History','Employment Start Date 5','employment_dates_5','date',false,4],
                    ['Employment History','Employment End Date 5','employment_end_5','date',false,4],
                    ['Employment History','Currently Employed Here? 5','currently_employed_5','select',false,4,['Yes','No']],

                    ['References','Reference Name 1','reference_name_1','text',false,3],
                    ['References','Position / Title 1','reference_title_1','text',false,3],
                    ['References','Company 1','reference_company_1','text',false,3],
                    ['References','Contact Number 1','reference_phone_1','text',false,3],
                    ['References','Reference Name 2','reference_name_2','text',false,3],
                    ['References','Position / Title 2','reference_title_2','text',false,3],
                    ['References','Company 2','reference_company_2','text',false,3],
                    ['References','Contact Number 2','reference_phone_2','text',false,3],
                    ['Documents','Resume / CV','resume','file',false,12],
                ],
            ],
            [
                'name' => 'Employment Questionnaire',
                'type' => 'questionnaire',
                'description' => 'Fixed employment questionnaire. Questions are system-managed in code to keep Assessment Insights stable.',
                'sort_order' => 2,
                'fields' => [
                    ['Motivation & Culture Fit','What is your priority in life: Growth or Financial Stability? Why?','priority_growth_financial','textarea',true,12],
                    ['Motivation & Culture Fit','If you were hired, what can you contribute to the company?','company_contribution','textarea',true,12],
                    ['Motivation & Culture Fit','How do you see yourself one year from now if you are hired and if not?','one_year_outlook','textarea',true,12],
                    ['Motivation & Culture Fit','What do you know about RS8 and Team RedSpeed?','knowledge_about_rs8','textarea',true,12],
                    ['Motivation & Culture Fit','Which do you prefer: Work-Life Balance or Work is Life? Why?','work_life_preference','textarea',true,12],
                    ['Role-Specific Assessment','Why are you interested in this specific position, and which part of the role matches your background best?','role_motivation','textarea',true,12],
                    ['Role-Specific Assessment','What skills or knowledge do you have that are directly relevant to this position?','role_relevant_skills','textarea',true,12],
                    ['Role-Specific Assessment','List the exact tools, software, systems, equipment, or platforms you have actually used that are relevant to this position. If none, type None.','role_tools_systems','text',true,12],
                    ['Role-Specific Assessment','Describe the work task, project, responsibility, or achievement from your experience that is most similar to this role. What exactly did you do and what was the result?','role_similar_project','textarea',true,12],
                    ['Role-Specific Assessment','Problem / Situation: Describe a difficult work-related problem you personally faced.','role_problem_solving','textarea',true,12],
                    ['Role-Specific Assessment','Action: What exactly did you personally do to address the problem?','role_problem_action','textarea',true,12],
                    ['Role-Specific Assessment','Result: What happened after your action? Give a concrete outcome when possible.','role_problem_result','textarea',true,12],
                    ['Role-Specific Assessment','Based on the job requirements, which requirement are you strongest in? Give a specific example that proves it.','role_strongest_requirement','textarea',true,12],
                    ['Role-Specific Assessment','Which requirement or responsibility of this position would you need the most training or support on?','role_training_gap','textarea',true,12],
                ],
            ],
        ];
    }

    /**
     * Return only the two code-defined forms. Database rows provide stable IDs;
     * labels/types/options/required/order shown to applicants are overlaid from
     * this class so database edits cannot silently change the public form.
     */
    public function templates(): Collection
    {
        $result = collect();

        foreach ($this->definitions() as $definition) {
            $template = FormTemplate::query()
                ->with('fields')
                ->where('name', $definition['name'])
                ->where('type', $definition['type'])
                ->first();

            if (!$template) {
                throw new RuntimeException("Missing system employment form: {$definition['name']}. Run migrations to restore the static form definitions.");
            }

            $databaseFields = $template->fields->keyBy('field_key');
            $staticFields = collect();

            foreach ($definition['fields'] as $index => $fieldDefinition) {
                [$section, $label, $key, $type, $required, $width] = array_slice($fieldDefinition, 0, 6);
                $field = $databaseFields->get($key);

                if (!$field) {
                    throw new RuntimeException("Missing system employment field: {$key}. Run migrations to restore the static form definitions.");
                }

                // Keep the database ID/relations, but force all editable presentation
                // and validation metadata to the code-defined values in memory.
                $field->forceFill([
                    'section' => $section,
                    'label' => $label,
                    'field_type' => $type,
                    'options' => $fieldDefinition[6] ?? null,
                    'placeholder' => $this->placeholderFor($key),
                    'is_required' => $required,
                    'sort_order' => $index + 1,
                    'width' => $width,
                ]);

                $staticFields->push($field);
            }

            $template->forceFill([
                'description' => $definition['description'],
                'is_active' => true,
                'sort_order' => $definition['sort_order'],
            ]);
            $template->setRelation('fields', $staticFields);
            $result->push($template);
        }

        return $result;
    }

    /**
     * Upsert the code-defined rows without deleting legacy fields. Keeping old rows
     * protects historical FormAnswer foreign keys while the active workflow ignores them.
     */
    public function syncToDatabase(): void
    {
        DB::transaction(function () {
            foreach ($this->definitions() as $definition) {
                $template = FormTemplate::updateOrCreate(
                    ['name' => $definition['name'], 'type' => $definition['type']],
                    [
                        'description' => $definition['description'],
                        'is_active' => true,
                        'sort_order' => $definition['sort_order'],
                    ]
                );

                foreach ($definition['fields'] as $index => $fieldDefinition) {
                    [$section, $label, $key, $type, $required, $width] = array_slice($fieldDefinition, 0, 6);
                    FormField::updateOrCreate(
                        ['form_template_id' => $template->id, 'field_key' => $key],
                        [
                            'section' => $section,
                            'label' => $label,
                            'field_type' => $type,
                            'options' => $fieldDefinition[6] ?? null,
                            'placeholder' => $this->placeholderFor($key),
                            'is_required' => $required,
                            'sort_order' => $index + 1,
                            'width' => $width,
                        ]
                    );
                }
            }
        });
    }

    public function applicationFieldKeys(): array
    {
        $application = collect($this->definitions())->firstWhere('type', 'application');

        return collect($application['fields'] ?? [])->pluck(2)->values()->all();
    }

    private function placeholderFor(string $key): ?string
    {
        return match ($key) {
            'age' => 'Auto-calculated from birthdate',
            'referred_by' => 'Name of referrer, if applicable',
            'college_course' => 'Example: Bachelor of Science in Information Technology',
            'role_tools_systems' => 'Example: Windows Server, Active Directory, Microsoft 365, DNS, DHCP. Type None if not applicable.',
            'role_relevant_skills' => 'List only skills you can support with actual work, project, school, or training evidence.',
            'role_problem_solving' => 'Describe the context and what made the problem difficult.',
            'role_problem_action' => 'State your own actions, decisions, checks, analysis, coordination, or solution steps.',
            'role_problem_result' => 'State the outcome. Include measurable improvement when available.',
            default => null,
        };
    }
}
