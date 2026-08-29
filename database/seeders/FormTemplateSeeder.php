<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use Illuminate\Database\Seeder;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $application = FormTemplate::updateOrCreate(
            ['name'=>'Application for Employment','type'=>'application'],
            ['description'=>'Official RedSpeed Motoworkz OPC employment application form.','is_active'=>true,'sort_order'=>1]
        );

        $fields = [
            ['Job Application Data','Availability','availability','text',false,6],
            ['Job Application Data','Applied Through','applied_through','select',true,6,['Walk In','Email','Facebook Page','Website','Job Fair','Employee Referral']],
            ['Job Application Data','Referred By','referred_by','text',false,6],
            ['Personal Information','Last Name','last_name','text',true,3],
            ['Personal Information','First Name','first_name','text',true,3],
            ['Personal Information','Middle Name','middle_name','text',false,3],
            ['Personal Information','Nickname','nickname','text',false,3],
            ['Personal Information','Present Address','present_address','textarea',true,12],
            ['Personal Information','Permanent Address','permanent_address','textarea',false,12],
            ['Personal Information','Birthdate','birthdate','date',true,3],
            ['Personal Information','Age','age','number',false,2],
            ['Personal Information','Gender','gender','select',true,2,['Male','Female','Prefer not to say']],
            ['Personal Information','Civil Status','civil_status','select',true,2,['Single','Married','Widowed','Separated','With Partner']],
            ['Personal Information','Religion','religion','text',false,2],
            ['Personal Information','Blood Type','blood_type','text',false,1],
            ['Personal Information','Cellphone Number','cellphone_number','text',true,4],
            ['Personal Information','Email Address','email_address','email',true,4],
            ['Personal Information','Facebook Account','facebook_account','text',false,4],
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
            ['Educational Background','Other Professional Qualifications','professional_qualifications','textarea',false,12],
            ['Employment History','Company Name 1','company_1','text',false,4],
            ['Employment History','Company Address 1','company_address_1','text',false,4],
            ['Employment History','Position Held 1','position_held_1','text',false,2],
            ['Employment History','Date of Employment 1','employment_dates_1','text',false,2],
            ['Employment History','Duties and Responsibilities 1','duties_1','textarea',false,12],
            ['Employment History','Company Name 2','company_2','text',false,4],
            ['Employment History','Company Address 2','company_address_2','text',false,4],
            ['Employment History','Position Held 2','position_held_2','text',false,2],
            ['Employment History','Date of Employment 2','employment_dates_2','text',false,2],
            ['Employment History','Duties and Responsibilities 2','duties_2','textarea',false,12],
            ['References','Reference Name 1','reference_name_1','text',false,3],
            ['References','Title 1','reference_title_1','text',false,3],
            ['References','Company 1','reference_company_1','text',false,3],
            ['References','Phone 1','reference_phone_1','text',false,3],
            ['References','Reference Name 2','reference_name_2','text',false,3],
            ['References','Title 2','reference_title_2','text',false,3],
            ['References','Company 2','reference_company_2','text',false,3],
            ['References','Phone 2','reference_phone_2','text',false,3],
            ['Documents','Resume / CV','resume','file',false,12],
        ];
        $this->createFields($application, $fields);

        $questionnaire = FormTemplate::updateOrCreate(
            ['name'=>'Employment Questionnaire','type'=>'questionnaire'],
            ['description'=>'Applicant written questionnaire used during initial evaluation.','is_active'=>true,'sort_order'=>2]
        );
        $questions = [
            ['Questionnaire','What is your priority in life: Growth or Financial Stability? Why?','priority_growth_financial','textarea',true,12],
            ['Questionnaire','If you were hired, what can you contribute to the company?','company_contribution','textarea',true,12],
            ['Questionnaire','How do you see yourself one year from now if you are hired and if not?','one_year_outlook','textarea',true,12],
            ['Questionnaire','What do you know about RS8 and Team RedSpeed?','knowledge_about_rs8','textarea',true,12],
            ['Questionnaire','Which do you prefer: Work-Life Balance or Work is Life? Why?','work_life_preference','textarea',true,12],
        ];
        $this->createFields($questionnaire, $questions);
    }

    private function createFields(FormTemplate $template, array $fields): void
    {
        foreach ($fields as $index=>$field) {
            [$section,$label,$key,$type,$required,$width] = array_slice($field,0,6);
            $template->fields()->updateOrCreate(
                ['field_key'=>$key],
                [
                    'section'=>$section,'label'=>$label,'field_type'=>$type,
                    'options'=>$field[6]??null,'placeholder'=>null,'is_required'=>$required,
                    'sort_order'=>$index+1,'width'=>$width
                ]
            );
        }
    }
}
