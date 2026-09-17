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
    $v=new JobVacancy(['title'=>$title,'description'=>'','qualifications'=>implode("\n",$texts)]);
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

$check(AssessmentInsightService::MODEL_VERSION>=12,'assessment model is V12 or newer');

$seeder=new ItSpecialistSampleApplicantsSeeder();
$sm=new ReflectionMethod($seeder,'samples'); $sm->setAccessible(true); $samples=$sm->invoke($seeder);
$qm=new ReflectionMethod($seeder,'qualificationTexts'); $qm->setAccessible(true); $texts=$qm->invoke($seeder);
$meta=[['education','required','high'],['experience','required','high'],['skill','required','critical'],['skill','preferred','medium'],['skill','required','high']];
$rows=[]; foreach($texts as $i=>$text){ $rows[]=[$text,$meta[$i][0],$meta[$i][1],$meta[$i][2]]; }
$demo=$vacancy($rows,'IT SPECIALIST - ASSESSMENT DEMO');
$marco=array_merge($samples[0]['application'],$samples[0]['questionnaire']);
$andrea=array_merge($samples[1]['application'],$samples[1]['questionnaire']);

$marcoProblem=trim(($marco['role_problem_solving']??'').' '.($marco['role_problem_action']??'').' '.($marco['role_problem_result']??''));
$marcoContext=$invoke('problemRoleContextEvidenceLabels',[$demo,$marcoProblem]);
$check(count($marcoContext)>=2,'Marco SAR has non-zero required-role context matches',$marcoContext);
$check(in_array('Basic networking',$marcoContext,true) || in_array('Networking',$marcoContext,true),'IP/DHCP/DNS/gateway evidence is recognized as networking context',$marcoContext);
$check(in_array('Documentation',$marcoContext,true),'documented fix is recognized as documentation context',$marcoContext);
$check(in_array('Troubleshooting / problem solving',$marcoContext,true) || in_array('IT support / troubleshooting',$marcoContext,true),'diagnostic actions are recognized as troubleshooting context',$marcoContext);

$optionalOnly=$invoke('problemRoleContextEvidenceLabels',[$demo,'I used Active Directory, DNS and DHCP.']);
$check(!in_array('Active Directory',$optionalOnly,true) && !in_array('DNS',$optionalOnly,true) && !in_array('DHCP',$optionalOnly,true),'preferred-only named tools do not inflate problem-solving role context',$optionalOnly);

$problem=$invoke('problemSolvingAssessment',[$marco,$demo]);
$check(!str_contains((string)($problem['comparison']['secondary']??''),'0 role-context match'),'problem-solving comparison no longer reports a false zero role-context match',$problem['comparison']??null);
$contextFact=collect($problem['comparison']['facts']??[])->firstWhere('label','Role-context evidence');
$check($contextFact && !str_contains((string)($contextFact['value']??''),'No required-role'),'comparison explicitly explains which required-role concepts were evidenced',$contextFact);

$mq=$invoke('qualificationMatchAssessment',[$demo,$marco]);
$check(($mq['required_total_count']??0)===4 && ($mq['required_matched_count']??0)===4,'required qualification counts remain 4/4',$mq);
$check(($mq['preferred_total_count']??0)===1 && ($mq['preferred_matched_count']??0)===1,'preferred qualification remains separate at 1/1',$mq);
$check(str_contains((string)($mq['comparison']['primary']??''),'4/4 required qualifications matched'),'qualification comparison primary is explicitly required-baseline',$mq['comparison']??null);
$check(str_contains((string)($mq['comparison']['secondary']??''),'required not met') && !str_contains((string)($mq['comparison']['secondary']??''),'5/5 matched'),'qualification secondary does not collapse required and optional rows into a fake 5/5 hard-requirement summary',$mq['comparison']??null);

$view=file_get_contents(__DIR__.'/../resources/views/recruitment/assessment-insights/index.blade.php');
$check(str_contains($view,'Required: ${metric.required_matched || 0}/${requiredTotal} met'),'comparison UI renders required counts separately');
$check(str_contains($view,'tie-breaker only; missing optional evidence does not reduce Role Fit'),'comparison UI clearly labels optional evidence as non-scoring');

printf("\nAll %d Assessment V12 production-readiness checks passed.\n",count($checks));
