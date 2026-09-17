<?php
// The execution container used for this delivery does not provide ext-mbstring.
// These narrow ASCII-safe polyfills exist only in this manual test harness so
// Laravel's string helpers can be exercised; production application code is untouched.
if (!function_exists('mb_split')) {
    function mb_split($pattern, $string, $limit = -1) { return preg_split('~' . str_replace('~', '\\~', $pattern) . '~u', $string, $limit); }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = null) { return strtolower($string); }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($string, $encoding = null) { return strtoupper($string); }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null) { return strlen($string); }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null) { return $length === null ? substr($string, $start) : substr($string, $start, $length); }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0, $encoding = null) { return strpos($haystack, $needle, $offset); }
}
if (!function_exists('mb_strrpos')) {
    function mb_strrpos($haystack, $needle, $offset = 0, $encoding = null) { return strrpos($haystack, $needle, $offset); }
}
if (!defined('MB_CASE_UPPER')) define('MB_CASE_UPPER', 0);
if (!defined('MB_CASE_LOWER')) define('MB_CASE_LOWER', 1);
if (!defined('MB_CASE_TITLE')) define('MB_CASE_TITLE', 2);
if (!function_exists('mb_convert_case')) {
    function mb_convert_case($string, $mode, $encoding = null) {
        return $mode === MB_CASE_UPPER ? strtoupper($string) : ($mode === MB_CASE_TITLE ? ucwords(strtolower($string)) : strtolower($string));
    }
}
require __DIR__ . '/../vendor/autoload.php';

use App\Models\JobVacancy;
use App\Models\JobVacancyQualification;
use App\Models\Position;
use App\Services\AssessmentInsightService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

$service = new AssessmentInsightService();

$invoke = static function (string $method, array $args = []) use ($service) {
    $r = new ReflectionMethod($service, $method);
    $r->setAccessible(true);
    return $r->invokeArgs($service, $args);
};

$vacancy = static function (array $texts): JobVacancy {
    $v = new JobVacancy([
        'title' => 'Accounting Associate',
        'description' => 'Handle accounting records, reconciliation, and financial documentation.',
        'qualifications' => implode("\n", $texts),
    ]);
    $v->setRelation('position', new Position(['name' => 'Accounting Associate']));
    $rows = [];
    foreach ($texts as $i => $text) {
        $rows[] = new JobVacancyQualification([
            'qualification_text' => $text,
            'qualification_type' => 'auto',
            'requirement_level' => 'required',
            'importance' => 'high',
            'sort_order' => $i + 1,
            'is_active' => true,
        ]);
    }
    $v->setRelation('qualificationsList', new EloquentCollection($rows));
    return $v;
};

$checks = [];
$check = static function (bool $condition, string $label, $detail = null) use (&$checks): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$label}" . ($detail !== null ? ' | ' . json_encode($detail) : '') . PHP_EOL);
        exit(1);
    }
    $checks[] = $label;
    echo "PASS: {$label}\n";
};

$systemText = 'Must have experience using System (e.g., NetSuite, QuickBooks, or similar). Experience with NetSuite is an advantage.';
$v = $vacancy([$systemText]);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I use a system to organize accounting records.'], $v]);
$check($r['status'] !== 'matched', 'generic word system is not accepted as accounting-software proof', $r);

$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I used QuickBooks for accounts payable and bank reconciliation for 18 months.'], $v]);
$check($r['status'] === 'matched' && str_contains($r['evidence'], 'QuickBooks'), 'QuickBooks direct evidence matches', $r);
$check(($r['preferred_bonus_matched'] ?? null) === false, 'QuickBooks does not falsely satisfy embedded NetSuite preference', $r);

$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I used NetSuite for accounts payable and reconciliation.'], $v]);
$check($r['status'] === 'matched' && ($r['preferred_bonus_matched'] ?? false) === true, 'NetSuite satisfies base system requirement and embedded preference', $r);

$v = $vacancy(['Must know QuickBooks. NetSuite is an advantage.']);
$rNetSuiteOnly = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I used NetSuite for posting and reconciliation.'], $v]);
$rQuickBooks = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I used QuickBooks for posting and reconciliation.'], $v]);
$check($rNetSuiteOnly['status'] !== 'matched' && ($rNetSuiteOnly['preferred_bonus_matched'] ?? false) === true, 'preferred NetSuite evidence does not replace required QuickBooks', $rNetSuiteOnly);
$check($rQuickBooks['status'] === 'matched' && ($rQuickBooks['preferred_bonus_matched'] ?? null) === false, 'required QuickBooks remains independent from NetSuite preference', $rQuickBooks);

$v = $vacancy(['NetSuite experience is preferred.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I used NetSuite for AP posting and reconciliation.'], $v]);
$check($r['status'] === 'matched', 'a purely preferred NetSuite qualification is still directly assessable', $r);

$v = $vacancy(['Must have experience using accounting software. NetSuite is an advantage.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'Used QuickBooks for invoicing and bank reconciliation.'], $v]);
$check($r['status'] === 'matched' && ($r['preferred_bonus_matched'] ?? null) === false, 'generic accounting-software requirement accepts named accounting tool while keeping NetSuite as preference', $r);

$v = $vacancy(['Must know NetSuite, QuickBooks, or similar accounting software.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'Used Xero for invoicing and bank reconciliation.'], $v]);
$check($r['status'] === 'matched' && str_contains($r['evidence'], 'Xero'), 'Xero is accepted only because accounting-software requirement explicitly allows similar', $r);

$v = $vacancy(['Must have experience using a POS system.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I used a POS system for sales transactions, receipts, and daily closing.'], $v]);
$check($r['status'] === 'matched', 'domain-specific proprietary/generic POS system evidence works without hard-coded tool catalog entry', $r);

$v = $vacancy(['Must have SQL experience.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I use MySQL for queries, joins, and reporting.'], $v]);
$check($r['status'] === 'matched', 'MySQL evidence can establish a generic SQL requirement without assuming the reverse', $r);

$v = $vacancy(['Must know Microsoft Excel and QuickBooks.']);
$rPartial = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I use Microsoft Excel for reports.'], $v]);
$rAll = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I use Microsoft Excel and QuickBooks for reporting and reconciliation.'], $v]);
$check($rPartial['status'] !== 'matched' && $rAll['status'] === 'matched', 'AND-connected named tools require all named tools, while complete evidence passes', [$rPartial, $rAll]);

$v = $vacancy(['Must have experience with NetSuite.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'Experienced in bookkeeping.', 'role_training_gap' => 'I need training in NetSuite.'], $v]);
$check($r['status'] === 'needs_verification', 'training-gap NetSuite mention never becomes positive proof', $r);

$v = $vacancy(['Strong attention to detail and accuracy']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I have strong attention to detail and accuracy.'], $v]);
$check($r['status'] === 'needs_verification', 'soft-skill self-claim requires verification', $r);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_problem_solving' => 'I reviewed the records, compared the entries, found a discrepancy, and corrected it before submission.'], $v]);
$check($r['status'] === 'matched', 'concrete attention-to-detail behavior can support requirement', $r);

$v = $vacancy(["Bachelor's degree in Accountancy, Accounting Technology, Finance, or related field"]);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['college_course' => 'Bachelor of Science in Information Technology'], $v]);
$check($r['status'] === 'not_matched', 'BSIT does not falsely match accounting/finance degree', $r);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['college_course' => 'Bachelor of Science in Economics'], $v]);
$check($r['status'] === 'needs_verification', 'plausibly related Economics degree is routed to verification instead of auto-pass/fail', $r);

$tagalog = 'Nireview ko ang records, kinumpara ko ang entries, at nalaman ko kung saan nanggaling ang problema bago ko inayos ang discrepancy.';
$check($invoke('actionSignalCount', [$tagalog]) > 0, 'Tagalog/Taglish action evidence is detected');
$check($invoke('resultSignalCount', [$tagalog]) > 0, 'Tagalog/Taglish result evidence is detected');

$months = $invoke('mergedIntervalMonths', [[
    [Carbon::parse('2024-01-01'), Carbon::parse('2025-01-01')],
    [Carbon::parse('2024-06-01'), Carbon::parse('2024-12-01')],
]]);
$check($months === 12, 'overlapping employment periods are counted once', $months);

$v = $vacancy(['Must be willing to work onsite.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['availability' => 'Immediately', 'notice_period' => 'None / Can start immediately'], $v]);
$check($r['status'] === 'not_evidenced', 'start availability does not falsely prove onsite willingness', $r);

$v = $vacancy(['Must be available to start within 2 weeks.']);
$rLate = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['availability' => 'Within 30 Days', 'notice_period' => '30 Days'], $v]);
$rSoon = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['availability' => 'Within 1 Week', 'notice_period' => '1 Week'], $v]);
$check($rLate['status'] === 'not_matched' && $rSoon['status'] === 'matched', 'start-window requirement compares actual availability timing', [$rLate, $rSoon]);

$rConflict = $invoke('evaluateQualification', [
    $v->qualificationsList->first(),
    ['availability' => 'Within 1 Week', 'notice_period' => '30 Days'],
    $v,
]);
$check($rConflict['status'] === 'not_matched', 'conflicting availability/notice uses the later conservative start window', $rConflict);

$vImmediate = $vacancy(['Must be available to start immediately.']);
$rImmediateConflict = $invoke('evaluateQualification', [
    $vImmediate->qualificationsList->first(),
    ['availability' => 'Within 1 Week', 'notice_period' => 'None / Can start immediately'],
    $vImmediate,
]);
$check($rImmediateConflict['status'] === 'not_matched', 'notice-period text cannot falsely override a later stated availability for immediate-start requirement', $rImmediateConflict);

$vSixty = $vacancy(['Must be available to start within 60 days.']);
$rOpenEnded = $invoke('evaluateQualification', [
    $vSixty->qualificationsList->first(),
    ['availability' => 'More than 30 Days', 'notice_period' => 'More than 30 Days'],
    $vSixty,
]);
$check($rOpenEnded['status'] === 'needs_verification', 'open-ended more-than-30-days availability is not falsely treated as within 60 days', $rOpenEnded);

$v = $vacancy(['NC II certification is required.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['relevant_certifications' => 'TESDA National Certificate II (NC II) - Computer Systems Servicing'], $v]);
$check($r['status'] === 'matched', 'NC II credential identifier is matched correctly', $r);

$v = $vacancy(['CPA license is required.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['relevant_certifications' => 'Microsoft Office Specialist'], $v]);
$check($r['status'] === 'not_evidenced', 'unrelated certificate does not falsely prove a required license is not held', $r);

$v = $vacancy(['CPA and CMA certifications are required.']);
$rPartial = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['relevant_certifications' => 'Certified Public Accountant (CPA)'], $v]);
$rAll = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['relevant_certifications' => 'Certified Public Accountant (CPA); Certified Management Accountant (CMA)'], $v]);
$check($rPartial['status'] === 'not_evidenced' && $rAll['status'] === 'matched', 'AND-connected credentials require complete credential evidence', [$rPartial, $rAll]);

$level = $invoke('inferRequirementLevel', ['Must know an accounting system. NetSuite is an advantage.']);
$check($level === 'required', 'hard requirement language wins over embedded preferred phrase', $level);

$fit = $invoke('fit', [82.0, 72, [
    'score' => 100,
    'coverage' => 70,
    'required_not_matched_count' => 0,
    'required_not_verified_count' => 1,
    'critical_not_verified_count' => 1,
]]);
$check($fit === 'Insufficient Evidence', 'critical unverified requirement blocks normal fit label', $fit);

// Different tool category must not be accepted merely because the line says "or similar".
$v = $vacancy(['Must know Microsoft Excel or similar spreadsheet software.']);
$r = $invoke('evaluateQualification', [$v->qualificationsList->first(), ['role_relevant_skills' => 'I build dashboards in Power BI.'], $v]);
$check($r['status'] !== 'matched', 'Power BI does not falsely satisfy Excel/spreadsheet requirement', $r);

// A number that only states tenure must not be a performance result.
$check($invoke('resultSignalCount', ['I worked there for 2 years.']) === 0, 'tenure number is not treated as performance result');

printf("\nAll %d Assessment V4 manual checks passed.\n", count($checks));
