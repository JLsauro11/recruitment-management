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
use Database\Seeders\AssessmentValidationDemoSeeder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

$service = new AssessmentInsightService();
$invoke = static function(string $method, array $args = []) use ($service) {
    $r = new ReflectionMethod($service, $method);
    $r->setAccessible(true);
    return $r->invokeArgs($service, $args);
};
$checkCount = 0;
$check = static function(bool $ok, string $label, $detail = null) use (&$checkCount) {
    if (!$ok) {
        fwrite(STDERR, 'FAIL: '.$label.($detail !== null ? ' | '.json_encode($detail, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) : '').PHP_EOL);
        exit(1);
    }
    $checkCount++;
    echo "PASS: {$label}\n";
};

$check(AssessmentInsightService::MODEL_VERSION >= 13, 'assessment model upgraded to V13');

$seeder = new AssessmentValidationDemoSeeder();
$rm = new ReflectionMethod($seeder, 'vacancyDefinitions');
$rm->setAccessible(true);
$definitions = $rm->invoke($seeder);
$check(count($definitions) === 2, 'cross-role validation seeder defines exactly two additional vacancies');
$check(collect($definitions)->every(fn ($d) => count($d['samples']) === 3), 'each validation vacancy has three different applicants');

$makeVacancy = static function(array $definition): JobVacancy {
    $v = new JobVacancy([
        'title' => $definition['title'],
        'description' => $definition['description'],
        'qualifications' => implode("\n", array_column($definition['qualifications'], 'text')),
    ]);
    $v->setRelation('position', new Position(['name' => $definition['position']]));
    $rows = [];
    foreach ($definition['qualifications'] as $i => $q) {
        $rows[] = new JobVacancyQualification([
            'qualification_text' => $q['text'],
            'qualification_type' => $q['type'],
            'requirement_level' => $q['level'],
            'minimum_value' => $q['minimum'] ?? null,
            'minimum_unit' => $q['unit'] ?? null,
            'importance' => $q['importance'],
            'sort_order' => $i + 1,
            'is_active' => true,
        ]);
    }
    $v->setRelation('qualificationsList', new EloquentCollection($rows));
    return $v;
};

$scoreSample = static function(JobVacancy $v, array $sample) use ($invoke): array {
    $a = array_merge($sample['application'], $sample['questionnaire']);
    $q = $invoke('qualificationMatchAssessment', [$v, $a]);
    $e = $invoke('experienceAssessment', [$a, $v]);
    $s = $invoke('skillsAssessment', [$a, $v]);
    $p = $invoke('problemSolvingAssessment', [$a, $v]);
    $supporting = round((($e['score'] ?? 0) * 30 + ($s['score'] ?? 0) * 45 + ($p['score'] ?? 0) * 25) / 100, 1);
    $overall = round((($q['score'] ?? 0) * .45) + ($supporting * .55), 1);
    return compact('a','q','e','s','p','supporting','overall');
};

$all = [];
foreach ($definitions as $definition) {
    $vacancy = $makeVacancy($definition);
    $rows = array_map(fn ($sample) => $scoreSample($vacancy, $sample), $definition['samples']);
    $all[$definition['code']] = [$vacancy, $rows];

    $check($rows[0]['overall'] > $rows[1]['overall'] && $rows[1]['overall'] > $rows[2]['overall'], $definition['code'].' demo ranking follows strong > moderate > gap applicant', array_column($rows, 'overall'));
    $check(($rows[0]['q']['required_not_matched_count'] ?? 0) === 0 && ($rows[0]['q']['required_not_verified_count'] ?? 0) === 0, $definition['code'].' strongest applicant has no required gap/pending requirement', $rows[0]['q']);
    $check(($rows[2]['q']['required_not_matched_count'] ?? 0) >= 1, $definition['code'].' weakest applicant has at least one confirmed required gap', $rows[2]['q']);
}

[$accountingVacancy, $accountingRows] = $all['ACC'];
$nina = $accountingRows[2]['q'];
$bankGap = collect($nina['qualification_details'])->first(fn ($d) => str_contains(strtolower($d['qualification']), 'bank reconciliation'));
$journalGap = collect($nina['qualification_details'])->first(fn ($d) => str_contains(strtolower($d['qualification']), 'journal entries'));
$check(($bankGap['status'] ?? null) === 'not_matched', 'explicit "do not yet have bank-reconciliation experience" is a confirmed gap, not a false positive', $bankGap);
$check(($journalGap['status'] ?? null) === 'not_matched', 'explicit "do not yet have journal-entry experience" is a confirmed gap, not a false positive', $journalGap);

[$inventoryVacancy, $inventoryRows] = $all['INV'];
foreach ([0,1] as $index) {
    $problemRequirement = collect($inventoryRows[$index]['q']['qualification_details'])->first(fn ($d) => str_contains(strtolower($d['qualification']), 'problem solving'));
    $check(($problemRequirement['status'] ?? null) === 'matched', 'concrete inventory discrepancy investigation is recognized as problem-solving evidence for applicant '.($index + 1), $problemRequirement);
}

$view = file_get_contents(__DIR__.'/../resources/views/recruitment/assessment-insights/index.blade.php');
$check(str_contains($view, 'view exact requirement'), 'ranking gap count is clickable and traceable to the exact required requirement');
$check(str_contains($view, 'Confirmed required qualification gap'), 'ranking contains a dedicated confirmed-gap detail panel');
$check(str_contains($view, "gapDetail['qualification']") && str_contains($view, "gapDetail['evidence']"), 'gap detail panel shows exact qualification and why/evidence');
$check(str_contains($view, 'Open full assessment insight'), 'gap panel links HR to the full evidence assessment');

$databaseSeeder = file_get_contents(__DIR__.'/../database/seeders/DatabaseSeeder.php');
$check(str_contains($databaseSeeder, 'AssessmentValidationDemoSeeder::class'), 'local/testing DatabaseSeeder includes the two cross-role validation vacancies');

printf("\nAll %d Assessment V13 cross-role/traceability checks passed.\n", $checkCount);
