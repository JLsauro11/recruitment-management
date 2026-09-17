<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use App\Services\AssessmentInsightService;
use Illuminate\Http\Request;

class AssessmentInsightController extends Controller
{
    public function index(Request $request, AssessmentInsightService $service)
    {
        $vacancies = JobVacancy::with('position.department')
            ->withCount('applications')
            ->orderByDesc('opening_date')
            ->orderByDesc('id')
            ->get();

        $selected = $request->integer('vacancy')
            ? $vacancies->firstWhere('id', $request->integer('vacancy'))
            : ($vacancies->first(fn ($vacancy) => $vacancy->applications_count > 0) ?? $vacancies->first());

        $results = collect();
        $profile = null;
        $criteria = collect();
        $templates = collect();
        $definition = $service->automaticDefinition();
        $readiness = [
            'automatic' => true,
            'score' => 0,
            'criteria_count' => count($definition['criteria'] ?? []),
            'templates_count' => 0,
            'qualification_count' => 0,
            'qualification_ready' => false,
            'forms_ready' => false,
            'missing_fields' => [],
            'ready' => false,
        ];

        if ($selected) {
            $profile = $service->ensureVacancyProfile($selected);
            $criteria = $profile->criteria;
            $templates = $service->templatesForVacancy($selected);
            $readiness = $service->assessmentReadiness($selected, $profile);

            $selected->load([
                'applications' => fn ($query) => $query
                    ->with(['applicant', 'assessmentResult', 'vacancy.position', 'vacancy.qualificationsList',
                        'formSubmissions.template', 'formSubmissions.answers.field'])
                    ->orderByDesc('applied_at'),
            ]);

            // One-time automatic upgrade for legacy/stale scores. Normal day-to-day
            // changes are already re-scored at submission/edit/vacancy-update time, so
            // opening this page does not need to recalculate every applicant forever.
            foreach ($selected->applications as $application) {
                $result = $application->assessmentResult;
                $firstScoreRow = collect($result?->category_scores ?? [])->first();
                $modelVersion = (int) data_get($firstScoreRow, 'model_version', 0);
                $vacancyChangedAfterAssessment = $result?->assessed_at
                    ? $selected->updated_at?->gt($result->assessed_at)
                    : true;

                if (!$result || $modelVersion < AssessmentInsightService::MODEL_VERSION || $vacancyChangedAfterAssessment
                    || data_get($firstScoreRow, 'input_fingerprint') !== $service->inputFingerprint($application)) {
                    $service->assess($application, $profile);
                    $application->load('assessmentResult');
                }
            }

            $results = $selected->applications
                ->sort(function ($left, $right) {
                    // A confirmed required-qualification failure is a stronger negative
                    // signal than missing evidence. Never let a known hard gap outrank
                    // a candidate whose requirement is merely awaiting verification.
                    $qualificationStats = function ($application): array {
                        $qualification = collect($application->assessmentResult?->category_scores ?? [])
                            ->firstWhere('key', 'vacancy_qualification_match') ?? [];
                        return [
                            'required_failed' => (int) ($qualification['required_not_matched_count'] ?? 0),
                            'required_unverified' => (int) ($qualification['required_not_verified_count'] ?? 0),
                        ];
                    };
                    $leftQualification = $qualificationStats($left);
                    $rightQualification = $qualificationStats($right);
                    if ($leftQualification['required_failed'] !== $rightQualification['required_failed']) {
                        return $leftQualification['required_failed'] <=> $rightQualification['required_failed'];
                    }

                    // Evidence sufficiency is part of ranking reliability. A high raw
                    // score with insufficient evidence should not outrank a candidate
                    // whose fit is supported by assessable evidence.
                    $fitPriority = [
                        'Strong Fit' => 5,
                        'Good Fit' => 4,
                        'Moderate Fit' => 3,
                        'Needs Review' => 2,
                        'Insufficient Evidence' => 1,
                    ];
                    $leftPriority = $fitPriority[$left->assessmentResult?->fit_label ?? ''] ?? 0;
                    $rightPriority = $fitPriority[$right->assessmentResult?->fit_label ?? ''] ?? 0;
                    if ($leftPriority !== $rightPriority) {
                        return $rightPriority <=> $leftPriority;
                    }

                    if ($leftQualification['required_unverified'] !== $rightQualification['required_unverified']) {
                        return $leftQualification['required_unverified'] <=> $rightQualification['required_unverified'];
                    }

                    $leftScore = (float) ($left->assessmentResult?->overall_score ?? -1);
                    $rightScore = (float) ($right->assessmentResult?->overall_score ?? -1);
                    if ($leftScore !== $rightScore) {
                        return $rightScore <=> $leftScore;
                    }

                    // Preferred / nice-to-have qualifications and embedded advantages
                    // (for example "NetSuite is an advantage") are tie-breakers only.
                    // They never override a required qualification failure or reduce the
                    // candidate's hard-requirement baseline when absent.
                    $preferredStats = function ($application): array {
                        $qualification = collect($application->assessmentResult?->category_scores ?? [])
                            ->firstWhere('key', 'vacancy_qualification_match') ?? [];
                        return [
                            'matched' => (int) ($qualification['preferred_matched_count'] ?? 0)
                                + (int) ($qualification['preferred_bonus_matched_count'] ?? 0),
                            'strength' => (int) ($qualification['preference_evidence_strength'] ?? 0),
                        ];
                    };
                    $leftPreferred = $preferredStats($left);
                    $rightPreferred = $preferredStats($right);
                    if ($leftPreferred['matched'] !== $rightPreferred['matched']) {
                        return $rightPreferred['matched'] <=> $leftPreferred['matched'];
                    }
                    if ($leftPreferred['strength'] !== $rightPreferred['strength']) {
                        return $rightPreferred['strength'] <=> $leftPreferred['strength'];
                    }

                    // The legacy DB field is named confidence; V8 uses it strictly as
                    // Evidence Quality (completeness/specificity/coverage), not success probability or document verification.
                    return (int) ($right->assessmentResult?->confidence ?? 0)
                        <=> (int) ($left->assessmentResult?->confidence ?? 0);
                })
                ->values();
        }

        return view('recruitment.assessment-insights.index', compact(
            'vacancies',
            'selected',
            'results',
            'profile',
            'criteria',
            'templates',
            'readiness',
            'definition'
        ));
    }

    public function recalculate(Request $request, AssessmentInsightService $service)
    {
        $request->validate(['vacancy_id' => ['required', 'integer', 'exists:job_vacancies,id']]);
        $vacancy = JobVacancy::findOrFail($request->integer('vacancy_id'));
        $service->assessVacancy($vacancy);

        return back()->with('success', 'Assessment scores refreshed using the latest vacancy requirements and applicant evidence.');
    }

    /**
     * These compatibility endpoints intentionally do not accept manual configuration
     * anymore. Existing routes are kept so old bookmarks/forms fail safely instead of
     * producing a 404 after the automatic-assessment upgrade.
     */
    public function updateCriteria(Request $request, JobVacancy $vacancy, AssessmentInsightService $service)
    {
        return $this->automaticSetupNotice($vacancy, $service);
    }

    public function updateTemplates(Request $request, JobVacancy $vacancy, AssessmentInsightService $service)
    {
        return $this->automaticSetupNotice($vacancy, $service);
    }

    public function updateMappings(Request $request, JobVacancy $vacancy, AssessmentInsightService $service)
    {
        return $this->automaticSetupNotice($vacancy, $service);
    }

    public function savePositionDefault(JobVacancy $vacancy, AssessmentInsightService $service)
    {
        return $this->automaticSetupNotice($vacancy, $service);
    }

    public function resetPositionDefault(JobVacancy $vacancy, AssessmentInsightService $service)
    {
        return $this->automaticSetupNotice($vacancy, $service);
    }

    private function automaticSetupNotice(JobVacancy $vacancy, AssessmentInsightService $service)
    {
        $service->ensureVacancyProfile($vacancy);
        if ($vacancy->applications()->exists()) {
            $service->assessVacancy($vacancy);
        }

        return back()->with(
            'success',
            'Assessment Insights is now automatic. Criteria, fixed forms, weights, and evidence mapping no longer require HR/Admin setup.'
        );
    }
}
