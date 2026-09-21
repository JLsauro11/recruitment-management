<?php
if (!function_exists('mb_strtolower')) { function mb_strtolower($string,$encoding=null){ return strtolower($string); } }
if (!function_exists('config')) { function config($key, $default = null) { return $default; } }
require __DIR__.'/../app/Services/AiEvidenceInterpreter.php';

use App\Services\AiEvidenceInterpreter;

$service = new AiEvidenceInterpreter();
$method = new ReflectionMethod($service, 'canonicalizeCompetencies');
$method->setAccessible(true);

$check = static function(bool $ok, string $label, $detail = null): void {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$label}" . ($detail !== null ? ' | ' . json_encode($detail, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '') . PHP_EOL);
        exit(1);
    }
    echo "PASS: {$label}\n";
};

$english = 'Windows troubleshooting, hardware and software support, basic networking, user support, and documentation.';
$tagalogNormalized = 'I have experience in Windows troubleshooting, hardware and software support, basic networking, user support, and documentation.';
$taglishNormalized = 'Experienced in Windows troubleshooting, hardware/software support, basic networking, user support and documentation.';

$rawA = ['Windows support','hardware support','software support','networking','documentation','user support'];
$rawB = ['troubleshooting','Windows support','hardware and software support','networking','documentation','user support'];
$rawC = ['Windows support','hardware and software support','networking','documentation','user support'];

$a = $method->invoke($service, $rawA, $english, 'explicit', 0.99);
$b = $method->invoke($service, $rawB, $tagalogNormalized, 'semantically_supported', 0.98);
$c = $method->invoke($service, $rawC, $taglishNormalized, 'semantically_supported', 0.98);

$check($a === $b && $b === $c, 'Equivalent English/Tagalog/Taglish evidence normalizes to the same canonical set', compact('a','b','c'));
$check(in_array('Windows support', $a, true), 'Windows support retained', $a);
$check(in_array('hardware and software support', $a, true), 'Hardware/software synonyms collapse into one competency', $a);
$check(in_array('networking', $a, true), 'Networking retained', $a);
$check(in_array('documentation', $a, true), 'Documentation retained', $a);
$check(in_array('user support', $a, true), 'User support retained', $a);
$check(!in_array('hardware support', $a, true) && !in_array('software support', $a, true), 'Overlapping hardware/software labels are deduplicated', $a);

$low = $method->invoke($service, ['Windows support'], 'Windows support', 'ambiguous', 0.99);
$check($low === [], 'Ambiguous evidence cannot create positive canonical evidence', $low);

$weak = $method->invoke($service, [], 'I like IT.', 'explicit', 0.99);
$check($weak === [], 'Generic IT interest does not create competency evidence', $weak);


// A language variant must not gain the umbrella "system administration" competency
// merely because Gemini used that label while describing ordinary support work.
$englishSupport = $method->invoke($service,
    ['Windows support','networking'],
    'I troubleshot Windows connectivity, checked DHCP and DNS, and restored user access.',
    'semantically_supported', 0.99
);
$tagalogSupport = $method->invoke($service,
    ['Windows support','networking','system administration'],
    'I troubleshot Windows connectivity, checked DHCP and DNS, and restored user access. System administration.',
    'semantically_supported', 0.99
);
$check($englishSupport === $tagalogSupport, 'Umbrella system-administration label cannot create a language-only competency', compact('englishSupport','tagalogSupport'));
$check(!in_array('system administration', $tagalogSupport, true), 'System administration requires a concrete administration operation', $tagalogSupport);

$realAdmin = $method->invoke($service,
    ['system administration'],
    'I managed user accounts and permissions in Active Directory.',
    'explicit', 0.99
);
$check(in_array('system administration', $realAdmin, true), 'Concrete administration work still retains system administration evidence', $realAdmin);

printf("\nAll V22 language-invariance checks passed.\n");
