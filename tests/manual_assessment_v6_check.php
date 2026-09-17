<?php
if (!function_exists('mb_split')) { function mb_split($pattern,$string,$limit=-1){ return preg_split('~'.str_replace('~','\\~',$pattern).'~u',$string,$limit); } }
if (!function_exists('mb_strtolower')) { function mb_strtolower($string,$encoding=null){ return strtolower($string); } }
if (!function_exists('mb_strtoupper')) { function mb_strtoupper($string,$encoding=null){ return strtoupper($string); } }
if (!function_exists('mb_strlen')) { function mb_strlen($string,$encoding=null){ return strlen($string); } }
if (!function_exists('mb_substr')) { function mb_substr($string,$start,$length=null,$encoding=null){ return $length===null?substr($string,$start):substr($string,$start,$length); } }
if (!function_exists('mb_strpos')) { function mb_strpos($haystack,$needle,$offset=0,$encoding=null){ return strpos($haystack,$needle,$offset); } }
if (!function_exists('mb_strrpos')) { function mb_strrpos($haystack,$needle,$offset=0,$encoding=null){ return strrpos($haystack,$needle,$offset); } }
if (!defined('MB_CASE_UPPER')) define('MB_CASE_UPPER',0);
if (!defined('MB_CASE_LOWER')) define('MB_CASE_LOWER',1);
if (!defined('MB_CASE_TITLE')) define('MB_CASE_TITLE',2);
if (!function_exists('mb_convert_case')) { function mb_convert_case($string,$mode,$encoding=null){ return $mode===MB_CASE_UPPER?strtoupper($string):($mode===MB_CASE_TITLE?ucwords(strtolower($string)):strtolower($string)); } }
require __DIR__.'/../vendor/autoload.php';

use App\Models\JobVacancy;
use App\Models\JobVacancyQualification;
use App\Models\Position;
use App\Services\AssessmentInsightService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

$service = new AssessmentInsightService();
$invoke = static function(string $method,array $args=[]) use($service){ $r=new ReflectionMethod($service,$method); $r->setAccessible(true); return $r->invokeArgs($service,$args); };
$vacancy = static function(array $texts,string $title='IT Specialist'): JobVacancy {
    $v=new JobVacancy(['title'=>$title,'description'=>'Provide IT support, troubleshoot Windows devices, systems, and basic networks.','qualifications'=>implode("\n",$texts)]);
    $v->setRelation('position',new Position(['name'=>$title]));
    $rows=[];
    foreach($texts as $i=>$text){ $rows[]=new JobVacancyQualification(['qualification_text'=>$text,'qualification_type'=>'auto','requirement_level'=>'required','importance'=>'high','sort_order'=>$i+1,'is_active'=>true]); }
    $v->setRelation('qualificationsList',new EloquentCollection($rows));
    return $v;
};
$checks=[];
$check=static function(bool $ok,string $label,$detail=null) use (&$checks){ if(!$ok){ fwrite(STDERR,"FAIL: {$label}".($detail!==null?' | '.json_encode($detail):'').PHP_EOL); exit(1);} $checks[]=$label; echo "PASS: {$label}\n"; };

$check(AssessmentInsightService::MODEL_VERSION>=6,'assessment model remains V6-compatible or newer');

$keys=$service->fixedApplicationFieldKeys();
$removed=['permanent_address','facebook_account','college_completion_status','professional_qualifications','relevant_certifications','reason_leaving_1','duties_1','employment_tools_1','employment_achievements_1'];
$check(count(array_intersect($removed,$keys))===0,'removed V5 public-form fields are excluded from the fixed V6 application workflow',array_values(array_intersect($removed,$keys)));
$check(in_array('company_5',$keys,true) && in_array('currently_employed_5',$keys,true),'fixed V6 form supports up to five concise employment records');
$definition=$service->automaticDefinition();
$fitCriteria=array_values(array_filter($definition['criteria'],fn($row)=>($row['contributes_to_fit']??false)));
$check(array_sum(array_column($fitCriteria,'weight'))===100,'supporting fit criteria remain normalized to 100 percent',$fitCriteria);

$v=$vacancy(["Bachelor's degree in Information Technology, Computer Science, Information Systems, or related field"]);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['college_course'=>'Bachelor of Science in Information Technology'],$v]);
$check($r['status']==='matched','explicit bachelor degree wording can satisfy degree requirement without separate completion question',$r);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['college_course'=>'Information Technology'],$v]);
$check($r['status']==='needs_verification','generic course wording without degree level remains verification-only',$r);

$v=$vacancy(['At least 1 year of experience in IT support, help desk, system administration, or similar IT role']);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['work_experience_declaration'=>'No'],$v]);
$check($r['status']==='not_matched','explicit no-work-experience declaration is a real gap',$r);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),[
    'work_experience_declaration'=>'Yes','position_held_1'=>'IT Support Specialist','employment_dates_1'=>now()->subYears(2)->toDateString(),'currently_employed_1'=>'Yes'
],$v]);
$check($r['status']==='matched','role title plus valid employment dates satisfy minimum IT experience',$r);

$exp=$invoke('experienceAssessment',[[
    'work_experience_declaration'=>'Yes',
    'position_held_1'=>'IT Support Specialist','employment_dates_1'=>now()->subYears(2)->toDateString(),'currently_employed_1'=>'Yes',
    'position_held_3'=>'Help Desk Technician','employment_dates_3'=>now()->subYears(4)->toDateString(),'employment_end_3'=>now()->subYears(3)->toDateString(),'currently_employed_3'=>'No',
],$v]);
$check(($exp['score']??0)>0 && str_contains($exp['detail']??'','role-relevant'),'up to five employment records are supported by assessment',$exp);

$v=$vacancy(['Hands-on experience with Windows troubleshooting and basic networking']);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['role_relevant_skills'=>'Windows troubleshooting, TCP/IP and LAN support','role_tools_systems'=>'Windows 11, ipconfig, ping'],$v]);
$check(in_array($r['status'],['matched','needs_verification'],true),'role-specific questionnaire is used for technical-skill evidence',$r);

$v=$vacancy(['Must have experience using Active Directory.']);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['role_relevant_skills'=>'Active Directory'],$v]);
$check(in_array($r['status'],['not_evidenced','needs_verification'],true),'generic skill claim alone does not prove actual tool usage',$r);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['role_tools_systems'=>'I used Active Directory to create users, reset passwords, and manage group membership.'],$v]);
$check($r['status']==='matched','direct role-specific tool usage can satisfy tool requirement',$r);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['employment_tools_1'=>'Active Directory'],$v]);
$check($r['status']!=='matched','removed legacy employment-tools field cannot inflate a new V6 tool match',$r);

$v=$vacancy(['Strong troubleshooting, documentation, and user-support skills']);
$ps=$invoke('problemSolvingAssessment',[ [
    'role_problem_solving'=>'Hindi makakonekta sa shared folder ang ilang users pagkatapos ng network change.',
    'role_problem_action'=>'Nireview ko ang IP configuration, kinumpara ko ang DHCP leases, nakita ko ang duplicate IP at inayos ko ang configuration.',
    'role_problem_result'=>'Naibalik ang access ng lahat ng affected users at hindi na naulit ang duplicate IP issue sa sumunod na buwan.',
],$v]);
$check(($ps['coverage']??0)>=80 && ($ps['score']??0)>=60,'structured Tagalog/Taglish Situation-Action-Result remains assessable',$ps);

$v=$vacancy(['Must be available to start within 14 days.']);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['availability'=>'Within 1 Week','current_employment_status'=>'Unemployed','notice_period'=>'Not Applicable'],$v]);
$check($r['status']==='matched','concise availability fields can assess explicit start window',$r);
$v=$vacancy(['Must be willing to work onsite.']);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['availability'=>'Immediately','current_employment_status'=>'Unemployed'],$v]);
$check($r['status']==='not_evidenced','removed onsite question is not guessed from unrelated fields',$r);

$edu=$invoke('educationAssessment',[['college_school'=>'Sample University','college_course'=>'Bachelor of Science in Information Technology','college_dates'=>'2018 - 2022'],$vacancy([])]);
$check(($edu['score']??null)===null && str_contains($edu['detail']??'','Context only'),'education context does not double-count Role Fit',$edu);

$skills=$invoke('skillsAssessment',[['employment_tools_1'=>'Active Directory','employment_achievements_1'=>'Resolved many tickets'],$vacancy([])]);
$check($skills===null,'legacy removed employment-detail fields do not create supporting skill score',$skills);

printf("\nAll %d Assessment V6 manual checks passed.\n",count($checks));
