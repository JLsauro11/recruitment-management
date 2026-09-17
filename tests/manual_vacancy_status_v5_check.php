<?php
if (!function_exists('mb_split')) { function mb_split($pattern, $string, $limit = -1) { return preg_split('~' . str_replace('~', '\\~', $pattern) . '~u', $string, $limit); } }
if (!function_exists('mb_strtolower')) { function mb_strtolower($string, $encoding = null) { return strtolower($string); } }
if (!function_exists('mb_strtoupper')) { function mb_strtoupper($string, $encoding = null) { return strtoupper($string); } }
if (!function_exists('mb_strlen')) { function mb_strlen($string, $encoding = null) { return strlen($string); } }
if (!function_exists('mb_substr')) { function mb_substr($string, $start, $length = null, $encoding = null) { return $length === null ? substr($string, $start) : substr($string, $start, $length); } }
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$controller = new App\Http\Controllers\Admin\VacancyController();
$method = new ReflectionMethod($controller, 'normalizeStatusForDates');
$method->setAccessible(true);
$old = new App\Models\JobVacancy();
$old->setRawAttributes(['status'=>'Closed','opening_date'=>'2026-09-01','closing_date'=>'2026-09-07']);
$extended = $method->invoke($controller, ['status'=>'Closed','opening_date'=>'2026-09-01','closing_date'=>'2026-09-12'], $old);
$manual = new App\Models\JobVacancy();
$manual->setRawAttributes(['status'=>'Closed','opening_date'=>'2026-09-01','closing_date'=>'2026-09-12']);
$still = $method->invoke($controller, ['status'=>'Closed','opening_date'=>'2026-09-01','closing_date'=>'2026-09-15'], $manual);
$expired = $method->invoke($controller, ['status'=>'Open','opening_date'=>'2026-09-01','closing_date'=>'2026-09-07'], null);
$cases = [
 [($extended['status']??null)==='Open', 'expired auto-closed vacancy reopens when deadline is extended'],
 [($still['status']??null)==='Closed', 'intentional early/manual Closed stays Closed when old deadline was not expired'],
 [($expired['status']??null)==='Closed', 'Open vacancy with past deadline is forced Closed'],
];
foreach ($cases as [$ok,$message]) { if(!$ok){fwrite(STDERR,"FAIL: $message\n"); var_export([$extended,$still,$expired]); exit(1);} echo "PASS: $message\n"; }
