<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use Illuminate\Database\Seeder;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $application = FormTemplate::updateOrCreate(
            ['name' => 'Application for Employment', 'type' => 'application'],
            [
                'description' => 'Official RedSpeed Motoworkz OPC employment application with concise personal, education, employment-date, and readiness information for automated assessment.',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $fields = [
            // Job Application Data — intentionally short. These are the only
            // readiness/source questions collected before personal information.
            ['Job Application Data','Availability to Start','availability','select',true,4,['Immediately','Within 1 Week','Within 2 Weeks','Within 30 Days','More than 30 Days','To be discussed']],
            ['Job Application Data','Current Employment Status','current_employment_status','select',true,4,['Employed','Self-Employed','Unemployed','Student','Freelance / Project-Based']],
            ['Job Application Data','Notice Period','notice_period','select',false,4,['None / Can start immediately','1 Week','2 Weeks','30 Days','More than 30 Days','Not Applicable']],
            ['Job Application Data','Applied Through','applied_through','select',true,6,['Walk In','Email','Facebook Page','Website','Job Fair','Employee Referral']],
            ['Job Application Data','Referred By','referred_by','text',false,6],

            // Personal Information — administrative only; excluded from assessment scoring.
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

            // Educational Background — kept concise by request. Degree/Course remains
            // because it is the only structured education evidence used by Assessment Insights.
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
            // Employment History record 1
            ['Employment History','Company Name 1','company_1','text',false,4],
            ['Employment History','Company Address 1','company_address_1','text',false,4],
            ['Employment History','Position Held 1','position_held_1','text',false,4],
            ['Employment History','Employment Start Date 1','employment_dates_1','date',false,4],
            ['Employment History','Employment End Date 1','employment_end_1','date',false,4],
            ['Employment History','Currently Employed Here? 1','currently_employed_1','select',false,4,['Yes','No']],
            // Employment History record 2
            ['Employment History','Company Name 2','company_2','text',false,4],
            ['Employment History','Company Address 2','company_address_2','text',false,4],
            ['Employment History','Position Held 2','position_held_2','text',false,4],
            ['Employment History','Employment Start Date 2','employment_dates_2','date',false,4],
            ['Employment History','Employment End Date 2','employment_end_2','date',false,4],
            ['Employment History','Currently Employed Here? 2','currently_employed_2','select',false,4,['Yes','No']],
            // Employment History record 3
            ['Employment History','Company Name 3','company_3','text',false,4],
            ['Employment History','Company Address 3','company_address_3','text',false,4],
            ['Employment History','Position Held 3','position_held_3','text',false,4],
            ['Employment History','Employment Start Date 3','employment_dates_3','date',false,4],
            ['Employment History','Employment End Date 3','employment_end_3','date',false,4],
            ['Employment History','Currently Employed Here? 3','currently_employed_3','select',false,4,['Yes','No']],
            // Employment History record 4
            ['Employment History','Company Name 4','company_4','text',false,4],
            ['Employment History','Company Address 4','company_address_4','text',false,4],
            ['Employment History','Position Held 4','position_held_4','text',false,4],
            ['Employment History','Employment Start Date 4','employment_dates_4','date',false,4],
            ['Employment History','Employment End Date 4','employment_end_4','date',false,4],
            ['Employment History','Currently Employed Here? 4','currently_employed_4','select',false,4,['Yes','No']],
            // Employment History record 5
            ['Employment History','Company Name 5','company_5','text',false,4],
            ['Employment History','Company Address 5','company_address_5','text',false,4],
            ['Employment History','Position Held 5','position_held_5','text',false,4],
            ['Employment History','Employment Start Date 5','employment_dates_5','date',false,4],
            ['Employment History','Employment End Date 5','employment_end_5','date',false,4],
            ['Employment History','Currently Employed Here? 5','currently_employed_5','select',false,4,['Yes','No']],

            // References — verification context only.
            ['References','Reference Name 1','reference_name_1','text',false,3],
            ['References','Position / Title 1','reference_title_1','text',false,3],
            ['References','Company 1','reference_company_1','text',false,3],
            ['References','Contact Number 1','reference_phone_1','text',false,3],
            ['References','Reference Name 2','reference_name_2','text',false,3],
            ['References','Position / Title 2','reference_title_2','text',false,3],
            ['References','Company 2','reference_company_2','text',false,3],
            ['References','Contact Number 2','reference_phone_2','text',false,3],
            ['Documents','Resume / CV','resume','file',false,12],
        ];
        $this->createFields($application, $fields);

        $questionnaire = FormTemplate::updateOrCreate(
            ['name' => 'Employment Questionnaire', 'type' => 'questionnaire'],
            [
                'description' => 'Fixed employment questionnaire. Culture-preference answers remain informational; structured role evidence supports automated assessment.',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $questions = [
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
        ];
        $this->createFields($questionnaire, $questions);
    }

    private function createFields(FormTemplate $template, array $fields): void
    {
        foreach ($fields as $index => $field) {
            [$section, $label, $key, $type, $required, $width] = array_slice($field, 0, 6);
            $template->fields()->updateOrCreate(
                ['field_key' => $key],
                [
                    'section' => $section,
                    'label' => $label,
                    'field_type' => $type,
                    'options' => $field[6] ?? null,
                    'placeholder' => $this->placeholderFor($key),
                    'is_required' => $required,
                    'sort_order' => $index + 1,
                    'width' => $width,
                ]
            );
        }
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
