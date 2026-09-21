<?php
namespace Illuminate\Support {
    class Str {
        public static function lower(string $value): string { return strtolower($value); }
        public static function contains(string $haystack, string|array $needles): bool {
            foreach ((array) $needles as $needle) {
                if ($needle !== '' && str_contains($haystack, $needle)) return true;
            }
            return false;
        }
    }
}
namespace {
    class MiniCollection {
        public function __construct(private array $items) {}
        public function contains(callable $callback): bool {
            foreach ($this->items as $key => $value) {
                if ($callback($value, $key)) return true;
            }
            return false;
        }
    }
    function collect($items): MiniCollection { return new MiniCollection(is_array($items) ? $items : iterator_to_array($items)); }
}
namespace App\Services {
    require __DIR__ . '/../app/Services/AssessmentInsightService.php';
}
namespace {
    use App\Services\AssessmentInsightService;

    $service = new AssessmentInsightService();
    $extract = new ReflectionMethod($service, 'canonicalEvidenceCompetencies');
    $extract->setAccessible(true);
    $match = new ReflectionMethod($service, 'matchedSkillConcepts');
    $match->setAccessible(true);

    $check = static function (bool $ok, string $label, mixed $detail = null): void {
        if (!$ok) {
            fwrite(STDERR, 'FAIL: ' . $label . ($detail !== null ? ' | ' . json_encode($detail, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '') . PHP_EOL);
            exit(1);
        }
        echo 'PASS: ' . $label . PHP_EOL;
    };

    $en = [
        'role_relevant_skills' => 'Windows troubleshooting and user support. Canonical evidence: IT support; troubleshooting; Windows support; hardware and software support; networking; documentation; user support.',
        'role_similar_project' => 'Supported office users. Canonical evidence: troubleshooting; Windows support; networking; documentation; user support.',
        'role_strongest_requirement' => 'Hands-on help desk work. Canonical evidence: IT support; troubleshooting; Windows support; networking; documentation; user support.',
    ];
    $tl = [
        'role_relevant_skills' => 'May karanasan sa support. Canonical evidence: IT support; troubleshooting; Windows support; hardware and software support; networking; documentation; user support.',
        'role_similar_project' => 'Nag-support ng users. Canonical evidence: troubleshooting; Windows support; networking; documentation; user support.',
        'role_strongest_requirement' => 'Actual help desk work. Canonical evidence: IT support; troubleshooting; Windows support; networking; documentation; user support.',
    ];
    $tgl = [
        'role_relevant_skills' => 'Experienced sa support. Canonical evidence: IT support; troubleshooting; Windows support; hardware and software support; networking; documentation; user support.',
        'role_similar_project' => 'Nag-support ng office users. Canonical evidence: troubleshooting; Windows support; networking; documentation; user support.',
        'role_strongest_requirement' => 'Actual help desk work. Canonical evidence: IT support; troubleshooting; Windows support; networking; documentation; user support.',
    ];

    $keys = ['role_relevant_skills','role_similar_project','role_strongest_requirement'];
    $a = $extract->invoke($service, $en, $keys);
    $b = $extract->invoke($service, $tl, $keys);
    $c = $extract->invoke($service, $tgl, $keys);
    sort($a); sort($b); sort($c);

    $check($a === $b && $b === $c, 'Canonical scoring input is language-invariant', compact('a','b','c'));
    $check(count($a) === 7, 'Canonical scoring input is deduplicated across multiple fields', $a);

    $requiredContext = 'Hands-on experience with Windows troubleshooting, hardware/software support, basic networking, and strong troubleshooting, documentation, and user-support skills.';
    $matched = $match->invoke($service, $requiredContext, implode(' ', $a));
    sort($matched);
    $expected = [
        'Hardware / endpoint support',
        'IT support / troubleshooting',
        'Networking',
        'User support',
        'Windows support',
        'Documentation',
    ];
    sort($expected);
    $check($matched === $expected, 'Canonical phrases map to the full required competency family, including hardware/software', compact('matched','expected'));


    $problem = new ReflectionMethod($service, 'structuredProblemSemanticMetrics');
    $problem->setAccessible(true);

    // Same semantic evidence, deliberately different prose lengths/raw verb counts.
    $problemEn = $problem->invoke($service, 14, 28, 18, 1, 5, 2, 1, false, false, 4);
    $problemTl = $problem->invoke($service, 11, 19, 13, 2, 3, 1, 2, false, false, 3);
    $problemTgl = $problem->invoke($service, 9, 16, 10, 1, 2, 3, 1, false, false, 2);
    $check($problemEn['score'] === $problemTl['score'] && $problemTl['score'] === $problemTgl['score'],
        'Structured problem-solving score is invariant to equivalent paraphrase length/signal-count differences',
        ['en'=>$problemEn['score'],'tl'=>$problemTl['score'],'tgl'=>$problemTgl['score']]);
    $check($problemEn['coverage'] === 100 && $problemTl['coverage'] === 100 && $problemTgl['coverage'] === 100,
        'Equivalent complete SAR answers receive the same full structural coverage');

    $weakA = $problem->invoke($service, 4, 2, 2, 0, 1, 0, 0, false, false, 0);
    $weakB = $problem->invoke($service, 6, 3, 1, 1, 2, 0, 0, false, false, 1);
    $check($weakA['score'] <= 58 && $weakB['score'] <= 58,
        'Brief/weak action or result evidence remains capped despite wording differences',
        ['a'=>$weakA['score'],'b'=>$weakB['score']]);

    $fallback = $extract->invoke($service, ['role_relevant_skills' => 'plain legacy answer'], ['role_relevant_skills']);
    $check($fallback === [], 'Legacy answer without machine-readable canonical suffix cleanly falls back', $fallback);

    echo "\nAll V23 language-neutral scoring helper checks passed.\n";
}
