<?php
if (!function_exists('mb_split')) { function mb_split($pattern,$string,$limit=-1){ return preg_split('~'.str_replace('~','\\~',$pattern).'~u',$string,$limit); } }
if (!function_exists('mb_strtolower')) { function mb_strtolower($string,$encoding=null){ return strtolower($string); } }
if (!function_exists('mb_strtoupper')) { function mb_strtoupper($string,$encoding=null){ return strtoupper($string); } }
if (!function_exists('mb_strlen')) { function mb_strlen($string,$encoding=null){ return strlen($string); } }
if (!function_exists('mb_substr')) { function mb_substr($string,$start,$length=null,$encoding=null){ return $length===null?substr($string,$start):substr($string,$start,$length); } }
if (!function_exists('mb_strpos')) { function mb_strpos($haystack,$needle,$offset=0,$encoding=null){ return strpos($haystack,$needle,$offset); } }
if (!function_exists('mb_strrpos')) { function mb_strrpos($haystack,$needle,$offset=0,$encoding=null){ return strrpos($haystack,$needle,$offset); } }
if (!function_exists('mb_strimwidth')) { function mb_strimwidth($string,$start,$width,$trim_marker='',$encoding=null){ $full=substr($string,$start); if(strlen($full)<=$width) return $full; $keep=max(0,$width-strlen($trim_marker)); return substr($full,0,$keep).$trim_marker; } }
if (!defined('MB_CASE_UPPER')) define('MB_CASE_UPPER',0);
if (!defined('MB_CASE_LOWER')) define('MB_CASE_LOWER',1);
if (!defined('MB_CASE_TITLE')) define('MB_CASE_TITLE',2);
if (!function_exists('mb_convert_case')) { function mb_convert_case($string,$mode,$encoding=null){ return $mode===MB_CASE_UPPER?strtoupper($string):($mode===MB_CASE_TITLE?ucwords(strtolower($string)):strtolower($string)); } }

require __DIR__.'/../vendor/autoload.php';

use App\Models\FormField;
use App\Models\JobVacancy;
use App\Models\JobVacancyQualification;
use App\Models\Position;
use App\Services\AssessmentInsightService;
use Database\Seeders\ItSpecialistSampleApplicantsSeeder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

$service = new AssessmentInsightService();
$invoke = static function(string $method,array $args=[]) use($service){ $r=new ReflectionMethod($service,$method); $r->setAccessible(true); return $r->invokeArgs($service,$args); };
$vacancy = static function(array $rows,string $title='IT Specialist'): JobVacancy {
    $texts=array_map(fn($r)=>is_array($r)?$r[0]:$r,$rows);
    $v=new JobVacancy(['title'=>$title,'description'=>'Provide IT support, troubleshoot Windows devices, systems, and basic networks.','qualifications'=>implode("\n",$texts)]);
    $v->setRelation('position',new Position(['name'=>$title]));
    $models=[];
    foreach($rows as $i=>$row){
        if(!is_array($row)) $row=[$row,'auto','required','high'];
        [$text,$type,$level,$importance]=array_pad($row,4,null);
        $models[]=new JobVacancyQualification([
            'qualification_text'=>$text,'qualification_type'=>$type ?: 'auto','requirement_level'=>$level ?: 'required',
            'importance'=>$importance ?: 'high','sort_order'=>$i+1,'is_active'=>true,
        ]);
    }
    $v->setRelation('qualificationsList',new EloquentCollection($models));
    return $v;
};
$checks=[];
$check=static function(bool $ok,string $label,$detail=null) use (&$checks){ if(!$ok){ fwrite(STDERR,"FAIL: {$label}".($detail!==null?' | '.json_encode($detail,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE):'').PHP_EOL); exit(1);} $checks[]=$label; echo "PASS: {$label}\n"; };

$check(AssessmentInsightService::MODEL_VERSION>=7,'assessment model remains V7-compatible or newer');

// Regression: Company Name / Company Address must remain legitimate employment evidence.
$company=new FormField(['field_key'=>'company_1','label'=>'Company Name 1']);
$companyAddress=new FormField(['field_key'=>'company_address_1','label'=>'Company Address 1']);
$personalAddress=new FormField(['field_key'=>'present_address','label'=>'Address']);
$reference=new FormField(['field_key'=>'reference_name_1','label'=>'Reference Name 1']);
$check($invoke('excludedField',[$company])===false && $invoke('excludedField',[$companyAddress])===false,'employment company fields are no longer removed by privacy filtering');
$check($invoke('excludedField',[$personalAddress])===true && $invoke('excludedField',[$reference])===true,'personal address/reference data remain excluded from fit scoring');

// Related IT degree should satisfy an explicitly related-field requirement, while document verification remains an HR step.
$v=$vacancy([["Bachelor's degree in Information Technology, Computer Science, Information Systems, or a related field",'education','required','high']]);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['college_course'=>'Bachelor of Science in Computer Engineering'],$v]);
$check($r['status']==='matched' && ($r['related_field_match']??false)===true,'Computer Engineering is accepted as a related IT field when vacancy explicitly allows related fields',$r);

// Compound AND-style requirements cannot be satisfied by one keyword alone.
$v=$vacancy([['Hands-on experience with Windows troubleshooting, hardware/software support, and basic networking','skill','required','critical']]);
$r=$invoke('evaluateQualification',[$v->qualificationsList->first(),['role_relevant_skills'=>'Windows troubleshooting only','role_tools_systems'=>'Windows 11'],$v]);
$check($r['status']!=='matched','Windows alone cannot satisfy a three-part Windows + hardware/software + networking requirement',$r);
$r2=$invoke('evaluateQualification',[$v->qualificationsList->first(),[
    'role_relevant_skills'=>'Windows troubleshooting, desktop hardware diagnostics, software installation, and basic TCP/IP/LAN troubleshooting',
    'role_tools_systems'=>'Windows 11, ping, ipconfig',
],$v]);
$components=$r2['component_evidence']??[];
$check($r2['status']==='matched' && count(array_filter($components,fn($c)=>($c['status']??'')==='matched'))>=3,'compound requirement matches only when all required components have evidence',$r2);

// Pending required evidence must not display as a perfect qualification score.
$v=$vacancy([
    ["Bachelor's degree in Information Technology",'education','required','high'],
    ['Must have hands-on experience using Active Directory','skill','required','high'],
]);
$q=$invoke('qualificationMatchAssessment',[$v,[
    'college_course'=>'Bachelor of Science in Information Technology',
    'role_relevant_skills'=>'Active Directory', // claim only; no concrete-use evidence -> verification/pending
]]);
$check(($q['matched_count']??0)===1 && ($q['required_not_verified_count']??0)>=1,'pending required evidence stays pending instead of becoming a failure',$q);
$check(($q['assessed_match_rate']??0)===100 && ($q['score']??100)<100 && ($q['coverage']??100)<100,'pending requirement can show 100% among assessed items without misleading 100% overall qualification score',$q);

// Complete employment record should have full evidence coverage and a role-relevant title should count strongly.
$v=$vacancy([['At least 1 year of experience in IT support, help desk, system administration, or a similar IT role','experience','required','high']]);
$exp=$invoke('experienceAssessment',[ [
    'work_experience_declaration'=>'Yes','company_1'=>'ByteBridge Solutions','company_address_1'=>'Quezon City',
    'position_held_1'=>'IT Support Specialist','employment_dates_1'=>'2023-01-15','employment_end_1'=>'','currently_employed_1'=>'Yes',
],$v]);
$check(($exp['coverage']??0)===100 && ($exp['score']??0)>=80,'complete IT Support employment record reaches full coverage and strong relevant-experience score',$exp);

// Structured SAR action must make it through the scorer and produce high problem-solving evidence.
$problem=$invoke('problemSolvingAssessment',[ [
    'role_problem_solving'=>'Several users lost shared-folder access after a network change.',
    'role_problem_action'=>'I checked IP configuration, compared DHCP leases, tested gateway and DNS reachability, found duplicate static addresses, corrected the configuration, and documented the final settings.',
    'role_problem_result'=>'Access was restored the same day and the duplicate-IP issue did not recur during the following month.',
],$v]);
$check(($problem['coverage']??0)===100 && ($problem['score']??0)>=80,'complete Situation-Action-Result evidence is fully captured and scores as concrete problem solving',$problem);

// Missing Action should materially lower evidence quality/reliability inputs.
$qualityFull=$invoke('roleEvidenceQualityAssessment',[ [
    'role_relevant_skills'=>'Windows troubleshooting, basic networking','role_tools_systems'=>'Windows 11, TCP/IP',
    'role_similar_project'=>'Resolved a recurring endpoint issue for a department.',
    'role_problem_solving'=>'Users could not access a shared folder.','role_problem_action'=>'I traced DNS and permission settings and corrected the configuration.','role_problem_result'=>'Access was restored.',
    'role_strongest_requirement'=>'Daily Windows and user support experience.',
],$v]);
$qualityMissing=$invoke('roleEvidenceQualityAssessment',[ [
    'role_relevant_skills'=>'Windows troubleshooting, basic networking','role_tools_systems'=>'Windows 11, TCP/IP',
    'role_similar_project'=>'Resolved a recurring endpoint issue for a department.',
    'role_problem_solving'=>'Users could not access a shared folder.','role_problem_result'=>'Access was restored.',
    'role_strongest_requirement'=>'Daily Windows and user support experience.',
],$v]);
$check(($qualityMissing['coverage']??100)<($qualityFull['coverage']??0) && ($qualityMissing['score']??100)<($qualityFull['score']??0),'missing SAR Action lowers evidence coverage and quality rather than being hidden by other answers',[$qualityFull,$qualityMissing]);

// Seeded IT demo data should now produce directionally differentiated evidence, not the V6 72/45 artifacts.
$seeder=new ItSpecialistSampleApplicantsSeeder();
$sm=new ReflectionMethod($seeder,'samples'); $sm->setAccessible(true); $samples=$sm->invoke($seeder);
$qm=new ReflectionMethod($seeder,'qualificationTexts'); $qm->setAccessible(true); $texts=$qm->invoke($seeder);
$meta=[['education','required','high'],['experience','required','high'],['skill','required','critical'],['skill','preferred','medium'],['skill','required','high']];
$rows=[]; foreach($texts as $i=>$text){ $rows[]=[$text,$meta[$i][0],$meta[$i][1],$meta[$i][2]]; }
$demo=$vacancy($rows,'IT SPECIALIST - ASSESSMENT DEMO');
$marco=array_merge($samples[0]['application'],$samples[0]['questionnaire']);
$andrea=array_merge($samples[1]['application'],$samples[1]['questionnaire']);
$mx=$invoke('experienceAssessment',[$marco,$demo]); $ax=$invoke('experienceAssessment',[$andrea,$demo]);
$mp=$invoke('problemSolvingAssessment',[$marco,$demo]); $ap=$invoke('problemSolvingAssessment',[$andrea,$demo]);
$mq=$invoke('qualificationMatchAssessment',[$demo,$marco]); $aq=$invoke('qualificationMatchAssessment',[$demo,$andrea]);
$check(($mx['coverage']??0)===100 && ($ax['coverage']??0)===100 && ($mx['score']??0)>($ax['score']??0),'seeded Marco/Andrea employment evidence is complete and experience scores differentiate by role-relevant duration',[$mx,$ax]);
$check(($mp['score']??0)>=80 && ($ap['score']??0)>=80 && ($mp['coverage']??0)===100 && ($ap['coverage']??0)===100,'seeded Marco/Andrea Action answers reach Problem Solving instead of displaying Not provided',[$mp,$ap]);
$check(($mq['matched_count']??0)===5 && ($aq['matched_count']??0)===5 && ($aq['required_not_verified_count']??1)===0,'seeded IT demo qualification evidence resolves all five Marco/Andrea requirements after V7 related-field and compound-evidence fixes',[$mq,$aq]);

printf("\nAll %d Assessment V7 reliability checks passed.\n",count($checks));
