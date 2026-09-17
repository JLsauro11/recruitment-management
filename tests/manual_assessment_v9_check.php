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
    $v=new JobVacancy(['title'=>$title,'description'=>'General role description that may mention documentation and other non-required work.','qualifications'=>implode("\n",$texts)]);
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

$check(AssessmentInsightService::MODEL_VERSION>=9,'assessment model is V9 or newer');
$definition=$service->automaticDefinition();
$roleQuality=collect($definition['criteria'])->firstWhere('key','role_evidence_quality');
$check(($roleQuality['name']??'')==='Role Evidence Quality','role-only quality metric is explicitly named Role Evidence Quality',$roleQuality);
$check(str_contains(implode(' ', $definition['principles']??[]),'Overall Evidence Quality'),'method explains Overall vs Role Evidence Quality');

// Preferred/nice-to-have tools must not inflate Role-Specific Skills numeric Role Fit.
$v=$vacancy([
    ['Hands-on experience with Windows troubleshooting, hardware/software support, and basic networking','skill','required','critical'],
    ['Experience with Microsoft 365, Active Directory, DNS, DHCP, or similar administration tools is preferred','skill','preferred','medium'],
    ['Strong troubleshooting, documentation, and user-support skills','skill','required','high'],
]);
$base=[
    'role_relevant_skills'=>'Windows troubleshooting, hardware support, basic networking, documentation, and end-user support.',
    'role_tools_systems'=>'Windows 11',
    'role_similar_project'=>'I diagnosed a Windows workstation issue, corrected the network configuration, documented the fix, and restored user access.',
    'role_strongest_requirement'=>'I regularly troubleshoot Windows endpoints and support users.',
];
$withOptional=$base;
$withOptional['role_tools_systems']='Windows 11, Microsoft 365, Active Directory, DNS, DHCP';
$skillsBase=$invoke('skillsAssessment',[$base,$v]);
$skillsOptional=$invoke('skillsAssessment',[$withOptional,$v]);
$check(($skillsBase['score']??-1)===($skillsOptional['score']??-2),'optional tools do not change Role-Specific Skills numeric score',[$skillsBase,$skillsOptional]);
$optionalFact=collect($skillsOptional['comparison']['facts']??[])->firstWhere('label','Optional tools/systems (not scored)');
$check(str_contains((string)($optionalFact['value']??''),'Microsoft 365') && str_contains((string)($optionalFact['value']??''),'Active Directory'),'optional tools remain visible as not-scored evidence',$optionalFact);

// Documentation must not double-count as Data entry / records.
$requiredFact=collect($skillsBase['comparison']['facts']??[])->firstWhere('label','Matched required competencies');
$check(!str_contains((string)($requiredFact['value']??''),'Data entry / records'),'documentation no longer creates a false Data entry / records competency',$requiredFact);
$check(str_contains((string)($requiredFact['value']??''),'Documentation'),'documentation remains a valid required competency',$requiredFact);

// Preferred breadth/depth should distinguish candidates without changing required baseline.
$seeder=new ItSpecialistSampleApplicantsSeeder();
$sm=new ReflectionMethod($seeder,'samples'); $sm->setAccessible(true); $samples=$sm->invoke($seeder);
$qm=new ReflectionMethod($seeder,'qualificationTexts'); $qm->setAccessible(true); $texts=$qm->invoke($seeder);
$meta=[['education','required','high'],['experience','required','high'],['skill','required','critical'],['skill','preferred','medium'],['skill','required','high']];
$rows=[]; foreach($texts as $i=>$text){ $rows[]=[$text,$meta[$i][0],$meta[$i][1],$meta[$i][2]]; }
$demo=$vacancy($rows,'IT SPECIALIST - ASSESSMENT DEMO');
$marco=array_merge($samples[0]['application'],$samples[0]['questionnaire']);
$andrea=array_merge($samples[1]['application'],$samples[1]['questionnaire']);
$mq=$invoke('qualificationMatchAssessment',[$demo,$marco]);
$aq=$invoke('qualificationMatchAssessment',[$demo,$andrea]);
$check(($mq['score']??0)===100 && ($aq['score']??0)===100,'Marco and Andrea keep the same fully met required baseline',[$mq['score']??null,$aq['score']??null]);
$check(($mq['preferred_matched_count']??0)===1 && ($aq['preferred_matched_count']??0)===1,'both candidates still satisfy the preferred qualification status');
$check(($mq['preference_evidence_strength']??0)===100 && ($aq['preference_evidence_strength']??0)===25,'optional breadth uses literal named-technology coverage (4/4=100%, 1/4=25%)',[$mq['preference_evidence_strength']??null,$aq['preference_evidence_strength']??null]);
$mPref=collect($mq['qualification_details']??[])->first(fn($row)=>in_array($row['level']??'', ['preferred','nice_to_have'],true));
$aPref=collect($aq['qualification_details']??[])->first(fn($row)=>in_array($row['level']??'', ['preferred','nice_to_have'],true));
$check(str_contains((string)($mPref['preference_evidence_summary']??''),'4/4') && str_contains((string)($aPref['preference_evidence_summary']??''),'1/4'),'preferred row explains exact breadth difference',[$mPref,$aPref]);

// Required-only skill scoring should remain directionally meaningful for seeded data.
$ms=$invoke('skillsAssessment',[$marco,$demo]);
$as=$invoke('skillsAssessment',[$andrea,$demo]);
$check(($ms['score']??0)>($as['score']??0) && ($ms['score']??0)>=70 && ($as['score']??0)>=65,'seeded Role-Specific Skills remain differentiated after excluding optional tools from Role Fit',[$ms,$as]);

$view=file_get_contents(__DIR__.'/../resources/views/recruitment/assessment-insights/index.blade.php');
$check(str_contains($view,'Overall Evidence Quality') && str_contains($view,'Role Evidence Quality'),'UI clearly distinguishes overall and role-only evidence quality');
$check(str_contains($view,'Optional evidence:') && str_contains($view,'tie-breaker only') && !str_contains($view,'optional evidence depth'),'comparison UI shows exact optional evidence counts without a misleading synthetic percentage');

printf("\nAll %d Assessment V9 final-calibration checks passed.\n",count($checks));
