<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Department;
use App\Models\FormTemplate;
use App\Models\Interview;
use App\Models\JobVacancy;
use App\Models\Position;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $admin = User::create(['name'=>'System Administrator','email'=>'admin@rs8.com','password'=>Hash::make('password'),'role'=>'admin','status'=>'active']);
            $hr = User::create(['name'=>'HR Officer','email'=>'hr@rs8.com','password'=>Hash::make('password'),'role'=>'hr','status'=>'active']);

            $departments = collect([
                'Human Resources'=>'Handles recruitment, employee relations, and company policies.',
                'Sales'=>'Handles sales operations and client account management.',
                'Marketing'=>'Handles advertising, promotions, and brand campaigns.',
                'Accounting'=>'Handles financial records, payments, and reporting.',
                'Warehouse'=>'Handles stocks, inventory, and warehouse operations.',
                'Information Technology'=>'Handles systems, networks, and technical support.',
            ])->mapWithKeys(function ($description, $name) {
                $department = Department::create(compact('name','description') + ['status'=>'active']);
                return [$name=>$department];
            });

            $positionData = [
                ['Sales','Sales Executive'],['Accounting','Accounting Staff'],['Warehouse','Warehouse Staff'],
                ['Marketing','Marketing Assistant'],['Information Technology','IT Support'],
            ];
            $positions = collect($positionData)->mapWithKeys(function ($row) use ($departments) {
                $position = Position::create(['department_id'=>$departments[$row[0]]->id,'name'=>$row[1],'description'=>$row[1].' position.','status'=>'active']);
                return [$row[1]=>$position];
            });

            $vacancyInfo = [
                'Sales Executive'=>[3,18000,25000], 'Accounting Staff'=>[2,18000,23000],
                'Warehouse Staff'=>[4,15000,19000], 'Marketing Assistant'=>[1,18000,24000],
                'IT Support'=>[1,20000,28000],
            ];
            $vacancies = collect($vacancyInfo)->mapWithKeys(function ($info, $name) use ($positions) {
                $vacancy = JobVacancy::create([
                    'position_id'=>$positions[$name]->id,'title'=>$name,'slots'=>$info[0],
                    'employment_type'=>'Full-time','description'=>'Primary duties for the '.$name.' role.',
                    'qualifications'=>'Relevant education, skills, and experience.','salary_min'=>$info[1],
                    'salary_max'=>$info[2],'opening_date'=>now()->subDays(10),'closing_date'=>now()->addDays(30),'status'=>'Open'
                ]);
                return [$name=>$vacancy];
            });

            $this->seedTemplates();

            $sampleApplicants = [
                ['Juan',null,'Dela Cruz','juan@example.com','Sales Executive','For Initial Interview'],
                ['Maria','Lopez','Santos','maria@example.com','Accounting Staff','For Screening'],
                ['Carlo',null,'Mendoza','carlo@example.com','Warehouse Staff','For Questionnaire Review'],
                ['Angela','Reyes','Garcia','angela@example.com','Marketing Assistant','Hired'],
                ['Mark',null,'Villanueva','mark@example.com','IT Support','For Final Interview'],
            ];

            foreach ($sampleApplicants as $index => $row) {
                [$first,$middle,$last,$email,$vacancyName,$status] = $row;
                $applicant = Applicant::create([
                    'user_id'=>null,'first_name'=>$first,'middle_name'=>$middle,'last_name'=>$last,
                    'email'=>$email,'mobile'=>'0917000000'.($index+1),'address'=>'Santiago City, Isabela',
                    'birthdate'=>now()->subYears(25)->subDays($index)->toDateString(),'gender'=>$index%2?'Female':'Male'
                ]);
                $application = Application::create([
                    'applicant_id'=>$applicant->id,'job_vacancy_id'=>$vacancies[$vacancyName]->id,
                    'reference_no'=>'RS8-'.now()->year.'-'.str_pad($index+1,5,'0',STR_PAD_LEFT),
                    'status'=>$status,'applied_at'=>now()->subDays($index+2)
                ]);
                $application->statusHistories()->create(['status'=>'New Applicant','remarks'=>'Application created.','updated_by'=>$hr->id]);

                if (in_array($status,['For Initial Interview','For Questionnaire Review','For Final Interview','Hired'],true)) {
                    Interview::create([
                        'application_id'=>$application->id,'interviewer_id'=>$hr->id,'type'=>'Initial Interview',
                        'scheduled_at'=>$status==='For Initial Interview'?now()->addDay():now()->subDays(2),
                        'location'=>'HR Office','status'=>$status==='For Initial Interview'?'Scheduled':'Completed',
                        'remarks'=>'Seeded interview record.'
                    ]);
                }
            }

            foreach ([
                'company_name'=>'RedSpeed Motoworkz OPC','recruitment_email'=>'redspeedmotoworkzopc8@gmail.com',
                'contact_number'=>'(0966) 309 4660','office_address'=>'Redspeed Bldg., Abuang Street, Centro East, Santiago City, Isabela 3311',
                'default_application_status'=>'New Applicant',
            ] as $key=>$value) SystemSetting::create(compact('key','value'));
        });
    }

    private function seedTemplates(): void
    {
        $application = FormTemplate::create([
            'name'=>'Application for Employment','type'=>'application',
            'description'=>'Official RedSpeed Motoworkz OPC employment application form.','is_active'=>true,'sort_order'=>1
        ]);

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

        $questionnaire = FormTemplate::create([
            'name'=>'Employment Questionnaire','type'=>'questionnaire',
            'description'=>'Applicant written questionnaire used during initial evaluation.','is_active'=>true,'sort_order'=>2
        ]);
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
            $template->fields()->create([
                'section'=>$section,'label'=>$label,'field_key'=>$key,'field_type'=>$type,
                'options'=>$field[6]??null,'placeholder'=>null,'is_required'=>$required,
                'sort_order'=>$index+1,'width'=>$width
            ]);
        }
    }
}
