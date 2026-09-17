<?php

namespace App\Services;

use App\Models\{
    Application,
    AssessmentFieldMapping,
    AssessmentProfile,
    AssessmentProfileCriterion,
    AssessmentResult,
    FormField,
    FormTemplate,
    JobVacancy
};
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Automated, evidence-based recruitment assessment.
 *
 * Design goals:
 * - HR/Admin never has to create criteria, weights, template assignments, or mappings.
 * - The two fixed recruitment forms are the only assessment evidence source.
 * - Vacancy qualifications remain the primary role-fit baseline.
 * - Missing/unverified evidence is N/A, never an artificial 50% score.
 * - Sensitive/demographic fields are never used for scoring.
 * - Scores are conservative and explainable; interview verification is still required.
 */
class AssessmentInsightService
{
    public const MODEL_VERSION = 13;
    private const QUALIFICATION_WEIGHT = 45;
    private const SUPPORTING_WEIGHT = 55;

    /**
     * Sensitive / demographic fields that must never enter assessment evidence.
     *
     * IMPORTANT: these are exact field keys (plus the reference_* prefix handled
     * in excludedField()). Do not use broad substring rules such as "name" or
     * "address": those accidentally remove legitimate employment evidence like
     * company_1 and company_address_1 and can cap Relevant Experience incorrectly.
     *
     * @var array<int,string>
     */
    private array $excludedFieldKeys = [
        'last_name','first_name','middle_name','nickname',
        'present_address','permanent_address',
        'birthdate','age','gender','sex','civil_status','marital_status','religion','blood_type',
        'cellphone_number','phone','mobile','email_address','email','facebook_account','facebook',
    ];

    /**
     * Fixed supporting model. These weights total 100% inside the 55% supporting block.
     * Vacancy qualifications are scored separately at 45% of final Role Fit.
     *
     * @var array<int,array<string,mixed>>
     */
    private array $automaticCriteria = [
        [
            'key' => 'relevant_experience',
            'name' => 'Relevant Experience',
            'weight' => 30,
            'importance' => 'high',
            'purpose' => 'fit',
            'description' => 'Date-supported employment duration and role-title relevance. Detailed skills, tools, and results are assessed from the Role-Specific Assessment instead of asking applicants to repeat them in every employer record.',
            'fields' => [
                'work_experience_declaration',
                'position_held_1','employment_dates_1','employment_end_1','currently_employed_1',
                'position_held_2','employment_dates_2','employment_end_2','currently_employed_2',
                'position_held_3','employment_dates_3','employment_end_3','currently_employed_3',
                'position_held_4','employment_dates_4','employment_end_4','currently_employed_4',
                'position_held_5','employment_dates_5','employment_end_5','currently_employed_5',
            ],
        ],
        [
            'key' => 'role_specific_skills',
            'name' => 'Role-Specific Skills',
            'weight' => 45,
            'importance' => 'critical',
            'purpose' => 'fit',
            'description' => 'Direct evidence of vacancy-relevant tools, systems, technical competencies, and actual application from the role-specific questionnaire. Generic words alone do not count as proof.',
            'fields' => [
                'role_relevant_skills','role_tools_systems','role_strongest_requirement','role_similar_project',
            ],
        ],
        [
            'key' => 'problem_solving',
            'name' => 'Problem Solving & Results',
            'weight' => 25,
            'importance' => 'high',
            'purpose' => 'fit',
            'description' => "A concrete work problem with the applicant's own situation, action, and result. English and Filipino/Taglish evidence are recognized.",
            'fields' => ['role_problem_solving','role_problem_action','role_problem_result'],
        ],
        [
            'key' => 'role_evidence_quality',
            'name' => 'Role Evidence Quality',
            'weight' => 0,
            'importance' => 'medium',
            'purpose' => 'reliability',
            'description' => 'Measures completeness and specificity of the role-focused questionnaire only. This Role Evidence Quality row does not add Role Fit points and is separate from the overall Evidence Quality indicator.',
            'fields' => ['role_relevant_skills','role_tools_systems','role_similar_project','role_problem_solving','role_problem_action','role_problem_result','role_strongest_requirement'],
        ],
        [
            'key' => 'education_certifications',
            'name' => 'Education',
            'weight' => 0,
            'importance' => 'medium',
            'purpose' => 'context',
            'description' => 'Shows the concise education record submitted by the applicant. Any vacancy education requirement is scored once under Vacancy Qualification Match to prevent double counting.',
            'fields' => ['elementary_school','highschool_school','vocational_school','college_school','college_course','college_dates'],
        ],
        [
            'key' => 'availability_readiness',
            'name' => 'Availability & Readiness',
            'weight' => 0,
            'importance' => 'low',
            'purpose' => 'context',
            'description' => 'Basic start availability, current employment status, and notice period only. Any explicit vacancy readiness requirement is scored once under Vacancy Qualification Match.',
            'fields' => ['availability','current_employment_status','notice_period'],
        ],
    ];

    /** @var array<int,string> */
    private array $coreEvidenceFields = [
        'college_course','work_experience_declaration',
        'position_held_1','employment_dates_1','currently_employed_1',
        'role_relevant_skills','role_tools_systems','role_similar_project',
        'role_problem_solving','role_problem_action','role_problem_result','role_strongest_requirement',
    ];

    public function automaticDefinition(): array
    {
        return [
            'mode' => 'automatic',
            'model_version' => self::MODEL_VERSION,
            'qualification_weight' => self::QUALIFICATION_WEIGHT,
            'supporting_weight' => self::SUPPORTING_WEIGHT,
            'criteria' => collect($this->automaticCriteria)->map(fn ($row) => [
                'key' => $row['key'],
                'name' => $row['name'],
                'weight' => $row['weight'],
                'importance' => $row['importance'],
                'purpose' => $row['purpose'] ?? 'fit',
                'contributes_to_fit' => ($row['weight'] ?? 0) > 0 && ($row['purpose'] ?? 'fit') === 'fit',
                'description' => $row['description'],
            ])->values()->all(),
            'principles' => [
                'Vacancy qualifications are parsed automatically from the Job Vacancy record.',
                'The fixed concise Application for Employment and Employment Questionnaire are used automatically; applicants are not asked to repeat skills/tools/results inside each employer record.',
                'Missing evidence is labeled Not evidenced / Needs verification and does not receive artificial points; only supported requirements contribute to the evidence-supported qualification score.',
                'Sensitive and demographic information is excluded from assessment scoring.',
                'Culture-preference questions, demographics, availability context, and duplicate education evidence do not inflate Role Fit.',
                'Qualifications containing only optional preferences do not create a required baseline; add explicit required qualifications before relying on a fit label.',
                'Generic keyword repetition is not enough to satisfy a skill requirement; canonical competencies are deduplicated and compound requirements are checked component-by-component.',
                'Preferred and nice-to-have qualifications are shown as optional advantages/tie-breakers; missing them does not reduce the required-qualification baseline.',
                'Not met is reserved for directly contradictory evidence; missing or ambiguous proof is kept as Not evidenced / Needs verification.',
                'Overlapping employment dates are counted once so simultaneous jobs do not inflate date-supported experience.',
                'Overall Evidence Quality describes completeness, specificity, and assessable coverage across the assessment; Role Evidence Quality describes only the role-focused questionnaire. Neither is proof that applicant-provided claims are true or a probability of successful hiring/job performance.',
                'Applicant-provided claims are decision-support evidence and remain subject to interview, reference, or document verification.',
            ],
        ];
    }

    /**
     * Keep the existing database profile tables as an audit-friendly representation,
     * but synchronize them to one fixed model so there is no HR/Admin setup step.
     */
    public function ensureVacancyProfile(JobVacancy $vacancy): AssessmentProfile
    {
        $vacancy->loadMissing('position');

        $profile = AssessmentProfile::updateOrCreate(
            ['job_vacancy_id' => $vacancy->id],
            [
                'position_id' => $vacancy->position_id,
                'name' => ($vacancy->title ?: $vacancy->position?->name ?: 'Vacancy') . ' Automatic Assessment',
                'profile_type' => 'vacancy',
                'is_active' => true,
            ]
        );

        $templates = $this->templatesForVacancy($vacancy);
        $fieldsByKey = $templates->flatMap->fields->keyBy('field_key');
        $keepKeys = collect($this->automaticCriteria)->pluck('key')->all();

        DB::transaction(function () use ($profile, $fieldsByKey, $keepKeys) {
            foreach ($this->automaticCriteria as $index => $row) {
                $criterion = $profile->criteria()->updateOrCreate(
                    ['criterion_key' => $row['key']],
                    [
                        'name' => $row['name'],
                        'weight' => $row['weight'],
                        'importance' => $row['importance'],
                        'minimum_score' => null,
                        'is_required' => false,
                        'description' => $row['description'],
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]
                );

                $expectedFieldIds = collect($row['fields'])
                    ->map(fn ($key) => $fieldsByKey->get($key)?->id)
                    ->filter()
                    ->values();

                $criterion->fieldMappings()
                    ->when($expectedFieldIds->isNotEmpty(), fn ($q) => $q->whereNotIn('form_field_id', $expectedFieldIds->all()))
                    ->when($expectedFieldIds->isEmpty(), fn ($q) => $q)
                    ->delete();

                foreach ($expectedFieldIds as $fieldId) {
                    AssessmentFieldMapping::updateOrCreate(
                        [
                            'assessment_profile_criterion_id' => $criterion->id,
                            'form_field_id' => $fieldId,
                        ],
                        [
                            'scoring_method' => 'auto',
                            'max_score' => 10,
                            'rubric' => $row['description'],
                            'answer_key' => null,
                            'is_knockout' => false,
                        ]
                    );
                }
            }

            $profile->criteria()->whereNotIn('criterion_key', $keepKeys)->delete();
        });

        return $profile->fresh('criteria.fieldMappings.field');
    }

    public function assessmentReadiness(JobVacancy $vacancy, ?AssessmentProfile $profile = null): array
    {
        $profile ??= $this->ensureVacancyProfile($vacancy);
        $profile->loadMissing('criteria.fieldMappings.field');

        $templates = $this->templatesForVacancy($vacancy);
        $fieldKeys = $templates->flatMap->fields->pluck('field_key')->filter()->unique();
        $missingCore = collect($this->coreEvidenceFields)->reject(fn ($key) => $fieldKeys->contains($key))->values();

        $vacancy->loadMissing('qualificationsList');
        $qualificationCount = $vacancy->qualificationsList->where('is_active', true)->count();
        if ($qualificationCount === 0) {
            $qualificationCount = $this->freeTextQualifications($vacancy)->count();
        }

        $criteria = $profile->criteria->where('is_active', true)->values();
        $mappedCriteria = $criteria->filter(fn ($criterion) => $criterion->fieldMappings->isNotEmpty())->count();
        $mappedFields = $criteria->flatMap->fieldMappings->pluck('form_field_id')->unique()->count();

        $formScore = $missingCore->isEmpty() && $templates->count() >= 2
            ? 60
            : max(0, 60 - ($missingCore->count() * 4) - max(0, 2 - $templates->count()) * 15);
        $requiredQualificationCount = $this->qualificationRequirements($vacancy)->filter(
            fn ($row) => $this->effectiveRequirementLevel((string) ($row->requirement_level ?? ''), (string) $row->qualification_text) === 'required'
        )->count();
        $qualificationScore = $requiredQualificationCount > 0 ? 40 : 0;
        $score = min(100, $formScore + $qualificationScore);

        return [
            'automatic' => true,
            'score' => (int) $score,
            'total_weight' => 100,
            'criteria_count' => $criteria->count(),
            'mapped_criteria' => $mappedCriteria,
            'mapped_fields' => $mappedFields,
            'critical_unmapped' => 0,
            'templates_count' => $templates->count(),
            'qualification_count' => $qualificationCount,
            'required_qualification_count' => $requiredQualificationCount,
            'qualification_ready' => $requiredQualificationCount > 0,
            'forms_ready' => $missingCore->isEmpty() && $templates->count() >= 2,
            'missing_fields' => $missingCore->all(),
            'ready' => $requiredQualificationCount > 0 && $missingCore->isEmpty() && $templates->count() >= 2,
        ];
    }

    public function assess(Application $application, ?AssessmentProfile $profile = null): AssessmentResult
    {
        $application->loadMissing([
            'applicant',
            'vacancy.position.department',
            'vacancy.qualificationsList',
            'formSubmissions.template',
            'formSubmissions.answers.field',
        ]);

        $vacancy = $application->vacancy;
        $profile ??= $this->ensureVacancyProfile($vacancy);
        $criteria = $profile->criteria->where('is_active', true)->keyBy('criterion_key');

        $answerByKey = [];
        foreach ($application->formSubmissions->sortBy(fn ($row) => [
            $row->submitted_at?->getTimestamp() ?? 0, (int) $row->id,
        ]) as $submission) {
            // Only the two recruitment evidence forms may supply scoring keys.
            if (!$this->isEvidenceSubmission($submission)) {
                continue;
            }
            foreach ($submission->answers as $answer) {
                if (!$answer->field || $this->excludedField($answer->field)) {
                    continue;
                }
                $answerByKey[$answer->field->field_key] = $this->stringValue($answer->value);
            }
        }

        $scores = [];
        $weighted = 0.0;
        $scoredWeight = 0;
        $criticalGaps = [];

        foreach ($this->automaticCriteria as $definition) {
            $criterion = $criteria->get($definition['key']);
            if (!$criterion) {
                continue;
            }

            $specific = $this->scoreAutomaticCriterion($definition['key'], $answerByKey, $vacancy);
            $score = $specific['score'] ?? null;
            $coverage = (int) ($specific['coverage'] ?? 0);
            $detail = $specific['detail'] ?? 'No assessable role evidence submitted for this area.';
            $evidenceItems = $specific['evidence_items'] ?? [];
            $comparison = $specific['comparison'] ?? null;
            $purpose = $definition['purpose'] ?? 'fit';
            $contributesToFit = $purpose === 'fit' && (int) $definition['weight'] > 0;

            $scores[] = [
                'model_version' => self::MODEL_VERSION,
                'key' => $definition['key'],
                'name' => $definition['name'],
                'score' => $score,
                'weight' => $definition['weight'],
                'importance' => $definition['importance'],
                'purpose' => $purpose,
                'contributes_to_fit' => $contributesToFit,
                'minimum_score' => null,
                'coverage' => $coverage,
                'mapped_questions' => count($definition['fields']),
                'description' => $definition['description'],
                'detail' => $detail,
                'evidence_items' => $evidenceItems,
                'comparison' => $comparison,
            ];

            if ($score !== null && $contributesToFit) {
                $weighted += $score * $definition['weight'];
                $scoredWeight += $definition['weight'];
            }
        }

        $supportingOverall = $scoredWeight > 0 ? round($weighted / $scoredWeight, 1) : null;
        $supportingCoverage = $this->weightedCoverage($scores);
        $evidenceQualityScore = (int) (collect($scores)->firstWhere('key', 'role_evidence_quality')['score'] ?? 0);

        $qualificationAssessment = $this->qualificationMatchAssessment($vacancy, $answerByKey);
        if ($qualificationAssessment !== null) {
            array_unshift($scores, $qualificationAssessment);
            foreach ($qualificationAssessment['critical_gaps'] ?? [] as $gap) {
                $criticalGaps[] = $gap;
            }
        }

        $qualificationScore = $qualificationAssessment['score'] ?? null;
        if ($qualificationScore !== null && $supportingOverall !== null) {
            $overall = round(($qualificationScore * (self::QUALIFICATION_WEIGHT / 100)) + ($supportingOverall * (self::SUPPORTING_WEIGHT / 100)), 1);
        } elseif ($qualificationScore !== null) {
            $overall = (float) $qualificationScore;
        } elseif ($supportingOverall !== null) {
            $overall = (float) $supportingOverall;
        } else {
            $overall = 0.0;
        }

        $rawOverall = $overall;

        // Hard-requirement uncertainty/failure must be visible in the final number.
        // Missing evidence is never converted into a fake 0/50 score; these caps only
        // prevent an incomplete required baseline from appearing as a high-confidence fit.
        if (($qualificationAssessment['critical_not_matched_count'] ?? 0) > 0) {
            $overall = min($overall, 59.0);
        } elseif (($qualificationAssessment['required_not_matched_count'] ?? 0) > 0) {
            $overall = min($overall, 69.0);
        } elseif (($qualificationAssessment['critical_not_verified_count'] ?? 0) > 0) {
            $overall = min($overall, 74.0);
        } elseif (($qualificationAssessment['required_not_verified_count'] ?? 0) > 0) {
            $overall = min($overall, 79.0);
        }

        $readiness = $this->assessmentReadiness($vacancy, $profile);
        $qualificationCoverage = (int) ($qualificationAssessment['coverage'] ?? 0);

        // The legacy database column is named `confidence`, but V8 treats it as
        // Evidence Quality: assessable coverage + answer specificity + form readiness.
        // It is not document verification and is not a probability that the applicant will succeed or be hired.
        $confidence = (int) round(
            ($qualificationCoverage * .45)
            + ($supportingCoverage * .30)
            + ($evidenceQualityScore * .20)
            + ($readiness['score'] * .05)
        );
        if (($qualificationAssessment['critical_not_verified_count'] ?? 0) > 0) {
            $confidence = min($confidence, 74);
        } elseif (($qualificationAssessment['required_not_verified_count'] ?? 0) > 0) {
            $confidence = min($confidence, 84);
        }
        if ($supportingCoverage < 40) {
            $confidence = min($confidence, 70);
        }

        // A missing Action/Result is a material reliability gap even when the other
        // questionnaire areas are complete. Do not let weighted averages hide it.
        $problemRow = collect($scores)->firstWhere('key', 'problem_solving') ?? [];
        $problemCoverage = (int) ($problemRow['coverage'] ?? 0);
        if (($problemRow['score'] ?? null) !== null) {
            if ($problemCoverage < 60) {
                $confidence = min($confidence, 72);
            } elseif ($problemCoverage < 80) {
                $confidence = min($confidence, 82);
            }
            if ((int) ($problemRow['score'] ?? 100) <= 45) {
                $confidence = min($confidence, 75);
            }
        }
        if ($evidenceQualityScore < 55) {
            $confidence = min($confidence, 70);
        } elseif ($evidenceQualityScore < 70) {
            $confidence = min($confidence, 84);
        }
        // Full form coverage must not turn moderately specific answers into a near-100
        // Evidence Quality score. Completeness can improve confidence in the assessment,
        // but the overall evidence-quality indicator may exceed the answer-specificity
        // score by only a small margin.
        if ($evidenceQualityScore > 0) {
            $confidence = min($confidence, $evidenceQualityScore + 3);
        }

        if ($qualificationAssessment === null) {
            $confidence = min($confidence, 60);
        }
        $confidence = max(0, min(100, $confidence));

        $fit = $this->fit($overall, $confidence, $qualificationAssessment);

        $scored = collect($scores)->filter(fn ($row) =>
            $row['score'] !== null
            && (($row['key'] ?? '') === 'vacancy_qualification_match' || ($row['contributes_to_fit'] ?? false))
        );
        $strengths = $scored
            ->filter(fn ($row) => $row['score'] >= 80 && ($row['coverage'] ?? 0) >= 50)
            ->sortByDesc(fn ($row) => ($row['score'] * .75) + (($row['coverage'] ?? 0) * .25))
            ->take(3)
            ->map(fn ($row) => $row['name'] . ': ' . $this->shortDetail($row))
            ->values()
            ->all();
        if (!$strengths) {
            $strengths = ['No strong evidence-supported role-fit signal yet. Verify the applicant\'s most relevant experience, tools, and role-specific examples during interview.'];
        }

        $gaps = collect($criticalGaps);
        foreach ($scores as $row) {
            if ($gaps->count() >= 4) {
                break;
            }
            $isFitArea = ($row['key'] ?? '') === 'vacancy_qualification_match' || ($row['contributes_to_fit'] ?? false);
            if (!$isFitArea) {
                continue;
            }
            if ($row['score'] === null) {
                $gaps->push($row['name'] . ': insufficient assessable role evidence.');
            } elseif ($row['score'] < 65) {
                $gaps->push($row['name'] . ': ' . $row['score'] . '% — ' . $this->shortDetail($row));
            } elseif (($row['coverage'] ?? 0) < 50) {
                $gaps->push($row['name'] . ': low evidence coverage (' . ($row['coverage'] ?? 0) . '%).');
            }
        }
        if ($evidenceQualityScore < 60 && $gaps->count() < 4) {
            $gaps->push('Evidence Quality: answers are too generic/incomplete for a high-quality evidence profile.');
        }
        $gaps = $gaps->filter()->unique()->take(4)->values()->all();
        if (!$gaps) {
            $gaps = ['No major role-fit evidence gap detected from the submitted forms. Applicant claims should still be checked during interview/document verification.'];
        }

        $focus = $this->buildInterviewFocus($scores, $qualificationAssessment);

        $qSummary = $qualificationAssessment
            ? (($qualificationAssessment['required_total_count'] ?? 0) > 0
                ? (($qualificationAssessment['required_matched_count'] ?? 0) . '/' . ($qualificationAssessment['required_total_count'] ?? 0) . ' required qualifications matched')
                : (($qualificationAssessment['matched_count'] ?? 0) . '/' . ($qualificationAssessment['total_count'] ?? 0) . ' qualifications matched'))
                . (($qualificationAssessment['preferred_total_count'] ?? 0) > 0
                    ? '; ' . ($qualificationAssessment['preferred_matched_count'] ?? 0) . '/' . ($qualificationAssessment['preferred_total_count'] ?? 0) . ' preferred/nice-to-have evidenced'
                    : '')
                . '; ' . (($qualificationAssessment['required_total_count'] ?? 0) > 0
                    ? (($qualificationAssessment['required_not_matched_count'] ?? 0) . ' required not met; '
                        . ($qualificationAssessment['required_not_evidenced_count'] ?? 0) . ' required not evidenced; '
                        . ($qualificationAssessment['required_needs_verification_count'] ?? 0) . ' required need verification')
                    : (($qualificationAssessment['not_matched_count'] ?? 0) . ' not met; '
                        . ($qualificationAssessment['not_evidenced_count'] ?? 0) . ' not evidenced; '
                        . ($qualificationAssessment['needs_verification_count'] ?? 0) . ' need verification'))
            : 'no vacancy qualification baseline available';

        $summary = sprintf(
            '%s · Role Fit %.1f%% · Evidence quality %d%% (%s). %s. Supporting fit evidence %.1f%% coverage. Evidence quality measures completeness/specificity, not truth verification. Education/availability context is not double-counted, demographics/culture-preference answers are excluded, and applicant claims remain subject to interview/document verification.',
            $fit,
            $overall,
            $confidence,
            $this->reliabilityLabel($confidence),
            ucfirst($qSummary),
            $supportingCoverage
        );

        $scores[0]['input_fingerprint'] = $this->inputFingerprint($application);
        $scores[0]['calculation'] = [
            'qualification_score' => $qualificationScore,
            'supporting_score' => $supportingOverall,
            'supporting_weight_assessed' => $scoredWeight,
            'supporting_weight_total' => 100,
            'raw_overall' => $rawOverall,
            'final_overall' => $overall,
            'cap_applied' => $overall < $rawOverall,
        ];

        return AssessmentResult::updateOrCreate(
            ['application_id' => $application->id],
            [
                'overall_score' => $overall,
                'fit_label' => $fit,
                'confidence' => $confidence,
                'category_scores' => $scores,
                'strengths' => $strengths,
                'gaps' => $gaps,
                'interview_focus' => $focus,
                'summary' => $summary,
                'assessed_at' => now(),
            ]
        );
    }

    private function isEvidenceSubmission($submission): bool
    {
        return ($submission->template?->type === 'application'
                && $submission->template?->name === 'Application for Employment')
            || ($submission->template?->type === 'questionnaire'
                && $submission->template?->name === 'Employment Questionnaire');
    }

    public function inputFingerprint(Application $application): string
    {
        $application->loadMissing(['vacancy.position', 'vacancy.qualificationsList',
            'formSubmissions.template', 'formSubmissions.answers.field']);
        $vacancy = $application->vacancy;
        $submissions = $application->formSubmissions->sortBy('id')->map(fn ($submission) => [
            $submission->id, $submission->submitted_at?->toIso8601String(),
            $submission->template?->only(['name', 'type']),
            $submission->answers->sortBy('id')->map(fn ($answer) => [
                $answer->id, $answer->value,
                $answer->field?->only(['field_key', 'field_type', 'label']),
            ])->values()->all(),
        ])->values()->all();

        return hash('sha256', json_encode([
            self::MODEL_VERSION, now()->toDateString(),
            $vacancy->only(['id', 'title', 'description', 'qualifications']),
            $vacancy->position?->name,
            $vacancy->qualificationsList->sortBy('id')->map->getAttributes()->values()->all(),
            $submissions,
        ], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function assessVacancy(JobVacancy $vacancy): Collection
    {
        $profile = $this->ensureVacancyProfile($vacancy);

        return $vacancy->applications()
            ->with([
                'applicant',
                'vacancy.position.department',
                'vacancy.qualificationsList',
                'formSubmissions.template',
                'formSubmissions.answers.field',
            ])
            ->get()
            ->map(fn ($application) => $this->assess($application, $profile));
    }

    /**
     * Fixed public Application for Employment fields for Assessment V8.
     * Legacy V5 fields may remain in an existing database so historical answers are
     * not destroyed, but they are no longer shown or required for new applications.
     *
     * @return array<int,string>
     */
    public function fixedApplicationFieldKeys(): array
    {
        $keys = [
            'availability','current_employment_status','notice_period','applied_through','referred_by',
            'last_name','first_name','middle_name','nickname','present_address','birthdate','age','gender','civil_status','religion','blood_type','cellphone_number','email_address',
            'elementary_school','elementary_address','elementary_dates',
            'highschool_school','highschool_address','highschool_dates',
            'vocational_school','vocational_address','vocational_dates',
            'college_school','college_address','college_course','college_dates',
            'work_experience_declaration',
        ];

        for ($i = 1; $i <= 5; $i++) {
            array_push($keys,
                "company_{$i}", "company_address_{$i}", "position_held_{$i}",
                "employment_dates_{$i}", "employment_end_{$i}", "currently_employed_{$i}"
            );
        }

        return array_merge($keys, [
            'reference_name_1','reference_title_1','reference_company_1','reference_phone_1',
            'reference_name_2','reference_title_2','reference_company_2','reference_phone_2',
            'resume',
        ]);
    }

    /**
     * Fixed form set requested by the business: one Application for Employment
     * plus one Employment Questionnaire. Vacancy-specific template assignment is
     * intentionally ignored so HR never has to configure forms per vacancy.
     */
    public function templatesForVacancy(JobVacancy $vacancy): Collection
    {
        // Assessment V8 uses one fixed, system-managed employment form pair.
        // Never fall back to an arbitrary custom template: doing so could silently
        // remove evidence fields that the assessment model depends on.
        $templates = FormTemplate::with([
                'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('type', 'application')
                        ->where('name', 'Application for Employment');
                })->orWhere(function ($inner) {
                    $inner->where('type', 'questionnaire')
                        ->where('name', 'Employment Questionnaire');
                });
            })
            ->get();

        $application = $templates->first(fn ($template) => $template->type === 'application'
            && $template->name === 'Application for Employment');
        $questionnaire = $templates->first(fn ($template) => $template->type === 'questionnaire'
            && $template->name === 'Employment Questionnaire');

        if ($application) {
            $allowed = array_flip($this->fixedApplicationFieldKeys());
            $application->setRelation('fields', $application->fields
                ->filter(fn ($field) => isset($allowed[$field->field_key]))
                ->sortBy(fn ($field) => [(int) $field->sort_order, (int) $field->id])
                ->values());
        }

        return collect([$application, $questionnaire])->filter()->values();
    }

    private function scoreAutomaticCriterion(string $key, array $answers, JobVacancy $vacancy): ?array
    {
        return match ($key) {
            'relevant_experience' => $this->experienceAssessment($answers, $vacancy),
            'role_specific_skills' => $this->skillsAssessment($answers, $vacancy),
            'problem_solving' => $this->problemSolvingAssessment($answers, $vacancy),
            'role_evidence_quality' => $this->roleEvidenceQualityAssessment($answers, $vacancy),
            'education_certifications' => $this->educationAssessment($answers, $vacancy),
            'availability_readiness' => $this->availabilityAssessment($answers, $vacancy),
            default => null,
        };
    }

    private function qualificationMatchAssessment(JobVacancy $vacancy, array $answers): ?array
    {
        $requirements = $this->qualificationRequirements($vacancy);
        if ($requirements->isEmpty()) {
            return null;
        }

        $details = [];
        $verifiedWeighted = 0.0;
        $verifiedWeight = 0.0;
        $totalWeight = 0.0;
        // Required qualifications form the baseline score. Preferred / nice-to-have
        // items are reported separately and used as ranking tie-breakers so absence
        // of an optional advantage never masquerades as failure of a hard requirement.
        $coreVerifiedWeighted = 0.0;
        $coreVerifiedWeight = 0.0;
        $coreTotalWeight = 0.0;
        $preferenceTotalWeight = 0.0;
        $preferenceMatchedWeight = 0.0;
        $preferenceStrengthWeighted = 0.0;
        $preferenceStrengthWeight = 0.0;
        $matched = 0;
        $notMatched = 0;
        $notEvidenced = 0;
        $needsVerification = 0;
        $requiredNotMatched = 0;
        $requiredNotVerified = 0;
        $criticalNotMatched = 0;
        $criticalNotVerified = 0;
        $preferredBonusCount = 0;
        $preferredBonusMatched = 0;
        $criticalGaps = [];

        foreach ($requirements as $requirement) {
            $text = (string) ($requirement->qualification_text ?? '');
            $evaluation = $this->evaluateQualification($requirement, $answers, $vacancy);
            $level = $this->effectiveRequirementLevel((string) ($requirement->requirement_level ?? ''), $text);
            $importance = $this->effectiveImportance((string) ($requirement->importance ?? ''), $level, $text);
            $levelWeight = ['required' => 3, 'preferred' => 2, 'nice_to_have' => 1][$level] ?? 2;
            $importanceWeight = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1][$importance] ?? 2;
            $weight = $levelWeight * $importanceWeight;
            $totalWeight += $weight;
            $isCoreRequirement = $level === 'required';
            if ($isCoreRequirement) {
                $coreTotalWeight += $weight;
            } else {
                $preferenceTotalWeight += $weight;
            }

            $status = (string) ($evaluation['status'] ?? 'not_evidenced');

            // Optional requirements never reduce Role Fit, but HR still needs a fair
            // tie-breaker when two candidates both satisfy the same preferred item.
            // V9 measures evidence breadth/depth separately from the binary status.
            $preferenceEvidenceStrength = null;
            $preferenceEvidenceSummary = null;
            if (!$isCoreRequirement) {
                [$preferenceEvidenceStrength, $preferenceEvidenceSummary] = $this->optionalPreferenceEvidenceStrength(
                    $text,
                    $evaluation,
                    $status
                );
                $preferenceStrengthWeighted += $preferenceEvidenceStrength * $weight;
                $preferenceStrengthWeight += $weight;
            }

            if (!empty($evaluation['preferred_bonus'])) {
                $preferredBonusCount++;
                if (($evaluation['preferred_bonus_matched'] ?? false) === true) {
                    $preferredBonusMatched++;
                }
            }
            if ($status === 'matched') {
                $matched++;
                $verifiedWeighted += 100 * $weight;
                $verifiedWeight += $weight;
                if ($isCoreRequirement) {
                    $coreVerifiedWeighted += 100 * $weight;
                    $coreVerifiedWeight += $weight;
                } else {
                    $preferenceMatchedWeight += $weight;
                }
            } elseif ($status === 'not_matched') {
                $notMatched++;
                $verifiedWeight += $weight;
                if ($isCoreRequirement) {
                    $coreVerifiedWeight += $weight;
                } else {
                }
                if ($level === 'required') {
                    $requiredNotMatched++;
                    if ($importance === 'critical') {
                        $criticalNotMatched++;
                    }
                    $criticalGaps[] = 'Required qualification not met: ' . $text . ' — ' . $evaluation['evidence'];
                }
            } elseif ($status === 'needs_verification') {
                $needsVerification++;
                if ($level === 'required') {
                    $requiredNotVerified++;
                    if ($importance === 'critical') {
                        $criticalNotVerified++;
                    }
                    $criticalGaps[] = 'Verify required qualification: ' . $text . ' — ' . $evaluation['evidence'];
                }
            } else {
                $notEvidenced++;
                if ($level === 'required') {
                    $requiredNotVerified++;
                    if ($importance === 'critical') {
                        $criticalNotVerified++;
                    }
                    $criticalGaps[] = 'Required qualification not evidenced: ' . $text . '.';
                }
            }

            $details[] = [
                'qualification' => $text,
                'type' => $evaluation['type'],
                'level' => $level,
                'importance' => $importance,
                'status' => $status,
                'evidence' => $evaluation['evidence'],
                'metric' => $evaluation['metric'],
                'metric_label' => $evaluation['metric_label'],
                'requirement_value' => $evaluation['requirement_value'],
                'preferred_bonus' => $evaluation['preferred_bonus'] ?? null,
                'preferred_bonus_matched' => $evaluation['preferred_bonus_matched'] ?? null,
                'evidence_source' => $evaluation['evidence_source'] ?? null,
                'related_field_match' => (bool) ($evaluation['related_field_match'] ?? false),
                'component_evidence' => array_values((array) ($evaluation['component_evidence'] ?? [])),
                'preference_evidence_strength' => $preferenceEvidenceStrength,
                'preference_evidence_summary' => $preferenceEvidenceSummary,
            ];
        }

        // V8 separates hard requirements from optional advantages. When at least one
        // required item exists, the qualification score/coverage is based only on that
        // required baseline. Preferred and nice-to-have rows remain visible and can
        // break ties, but missing them does not reduce the hard-requirement score.
        $baselineVerifiedWeighted = $coreVerifiedWeighted;
        $baselineVerifiedWeight = $coreVerifiedWeight;
        $baselineTotalWeight = $coreTotalWeight;

        $assessedMatchRate = $baselineVerifiedWeight > 0 ? (int) round($baselineVerifiedWeighted / $baselineVerifiedWeight) : null;
        $score = ($baselineTotalWeight > 0 && $baselineVerifiedWeight > 0)
            ? (int) round($baselineVerifiedWeighted / $baselineTotalWeight)
            : null;
        $coverage = $baselineTotalWeight > 0 ? (int) round(($baselineVerifiedWeight / $baselineTotalWeight) * 100) : 0;
        $allCoverage = $totalWeight > 0 ? (int) round(($verifiedWeight / $totalWeight) * 100) : 0;
        $preferenceMatchRate = $preferenceTotalWeight > 0
            ? (int) round(($preferenceMatchedWeight / $preferenceTotalWeight) * 100)
            : null;
        $preferenceEvidenceStrength = $preferenceStrengthWeight > 0
            ? (int) round($preferenceStrengthWeighted / $preferenceStrengthWeight)
            : null;
        $notVerified = $notEvidenced + $needsVerification;

        $requiredDetails = collect($details)->where('level', 'required');
        $optionalDetails = collect($details)->whereIn('level', ['preferred', 'nice_to_have']);
        $requiredMatchedCount = $requiredDetails->where('status', 'matched')->count();
        $requiredNotMatchedCount = $requiredDetails->where('status', 'not_matched')->count();
        $requiredNotEvidencedCount = $requiredDetails->where('status', 'not_evidenced')->count();
        $requiredNeedsVerificationCount = $requiredDetails->where('status', 'needs_verification')->count();
        $preferredMatchedCount = $optionalDetails->where('status', 'matched')->count();

        $primary = $requiredDetails->isNotEmpty()
            ? ($requiredMatchedCount . '/' . $requiredDetails->count() . ' required qualifications matched')
            : ($matched . '/' . $requirements->count() . ' qualifications matched');
        if ($optionalDetails->isNotEmpty()) {
            $primary .= ' · ' . $preferredMatchedCount . '/' . $optionalDetails->count() . ' preferred/nice-to-have evidenced';
        }
        $secondary = $requiredDetails->isNotEmpty()
            ? ($requiredNotMatchedCount . ' required not met · '
                . $requiredNotEvidencedCount . ' required not evidenced · '
                . $requiredNeedsVerificationCount . ' required verify · '
                . $coverage . '% required-baseline evidence coverage')
            : ($notMatched . ' not met · ' . $notEvidenced . ' not evidenced · '
                . $needsVerification . ' verify · ' . $coverage . '% evidence coverage');
        if ($allCoverage !== $coverage) {
            $secondary .= ' · ' . $allCoverage . '% all-qualification evidence coverage';
        }
        if ($assessedMatchRate !== null) {
            $secondary .= ' · ' . $assessedMatchRate . '% match among assessed baseline requirements';
        }
        if ($preferenceMatchRate !== null) {
            $secondary .= ' · ' . $preferenceMatchRate . '% preferred/nice-to-have status rate';
        }
        if ($preferenceEvidenceStrength !== null) {
            $secondary .= ' · optional evidence breadth is shown per preferred requirement (tie-breaker only)';
        }
        if ($preferredBonusCount > 0) {
            $secondary .= ' · ' . $preferredBonusMatched . '/' . $preferredBonusCount . ' embedded preference(s) evidenced';
        }

        return [
            'model_version' => self::MODEL_VERSION,
            'key' => 'vacancy_qualification_match',
            'name' => 'Vacancy Qualification Match',
            'score' => $score,
            'weight' => self::QUALIFICATION_WEIGHT,
            'importance' => 'critical',
            'purpose' => 'fit',
            'contributes_to_fit' => true,
            'minimum_score' => null,
            'coverage' => $coverage,
            'all_qualification_coverage' => $allCoverage,
            'assessed_match_rate' => $assessedMatchRate,
            'preference_match_rate' => $preferenceMatchRate,
            'preference_evidence_strength' => $preferenceEvidenceStrength,
            'mapped_questions' => $requirements->count(),
            'description' => 'Direct vacancy-requirement match. Required qualifications form the scored baseline; preferred/nice-to-have items are shown separately and used only as optional advantages/tie-breakers. Uncertainty is separated from failure.',
            'detail' => $primary . ' · ' . $secondary . '.',
            'evidence_items' => collect($details)->map(fn ($row) => $row['qualification'] . ': ' . str_replace('_', ' ', $row['status']))->all(),
            'comparison' => [
                'metric' => $score,
                'primary' => $primary,
                'secondary' => $secondary,
            ],
            'qualification_details' => $details,
            'matched_count' => $matched,
            'not_matched_count' => $notMatched,
            'not_evidenced_count' => $notEvidenced,
            'needs_verification_count' => $needsVerification,
            // Backward-compatible aggregate used by existing UI/data consumers.
            'not_verified_count' => $notVerified,
            'required_not_matched_count' => $requiredNotMatched,
            'required_not_verified_count' => $requiredNotVerified,
            'critical_not_matched_count' => $criticalNotMatched,
            'critical_not_verified_count' => $criticalNotVerified,
            'preferred_bonus_count' => $preferredBonusCount,
            'preferred_bonus_matched_count' => $preferredBonusMatched,
            'required_total_count' => $requiredDetails->count(),
            'required_matched_count' => $requiredMatchedCount,
            'required_not_matched_count' => $requiredNotMatchedCount,
            'required_not_evidenced_count' => $requiredNotEvidencedCount,
            'required_needs_verification_count' => $requiredNeedsVerificationCount,
            'preferred_total_count' => $optionalDetails->count(),
            'preferred_matched_count' => $preferredMatchedCount,
            'total_count' => $requirements->count(),
            'critical_gaps' => $criticalGaps,
        ];
    }

    private function qualificationRequirements(JobVacancy $vacancy): Collection
    {
        $vacancy->loadMissing('qualificationsList');
        $requirements = $vacancy->qualificationsList->where('is_active', true)->values();
        if ($requirements->isNotEmpty()) {
            return $requirements;
        }

        return $this->freeTextQualifications($vacancy)->map(function ($text, $index) {
            return (object) [
                'qualification_text' => $text,
                'qualification_type' => 'auto',
                'requirement_level' => $this->inferRequirementLevel($text),
                'minimum_value' => null,
                'minimum_unit' => null,
                'evidence_source' => 'auto',
                'importance' => $this->inferRequirementLevel($text) === 'required' ? 'high' : 'medium',
                'sort_order' => $index + 1,
            ];
        })->values();
    }

    private function freeTextQualifications(JobVacancy $vacancy): Collection
    {
        $lines = preg_split('/\r\n|\r|\n|;/', (string) $vacancy->qualifications) ?: [];

        return collect($lines)
            ->map(fn ($line) => trim((string) preg_replace('/^[\-•*\s]+/u', '', (string) $line)))
            ->filter()
            ->values();
    }

    private function inferRequirementLevel(string $text): string
    {
        // Required language wins over a preferred phrase that may appear later in
        // the same sentence (e.g. "Must know an accounting system. NetSuite is an advantage").
        if (preg_match('/\b(must|required|mandatory|at\s+least|minimum)\b/i', $text)) {
            return 'required';
        }
        if (preg_match('/\b(nice\s+to\s+have|bonus|optional)\b/i', $text)) {
            return 'nice_to_have';
        }
        if (preg_match('/\b(preferred|preferably|advantage|an advantage|plus|desirable)\b/i', $text)) {
            return 'preferred';
        }
        return 'required';
    }

    private function effectiveRequirementLevel(string $configured, string $text): string
    {
        // Explicit hard language always wins. Otherwise preserve valid legacy
        // metadata when the text itself is neutral, and let explicit preference
        // language override stale metadata.
        if (preg_match('/\b(must|required|mandatory|at\s+least|minimum)\b/i', $text)) {
            return 'required';
        }
        if (preg_match('/\b(nice\s+to\s+have|bonus|optional)\b/i', $text)) {
            return 'nice_to_have';
        }
        if (preg_match('/\b(preferred|preferably|advantage|an\s+advantage|plus|desirable)\b/i', $text)) {
            return 'preferred';
        }
        if (in_array($configured, ['required','preferred','nice_to_have'], true)) {
            return $configured;
        }
        return 'required';
    }

    private function effectiveImportance(string $configured, string $level, string $text): string
    {
        if (preg_match('/\b(mandatory|must|required|required qualification|license required|licensed)\b/i', $text)) {
            return 'critical';
        }
        if (in_array($configured, ['critical','high','medium','low'], true)) {
            if ($level === 'required' && in_array($configured, ['medium','low'], true)) {
                return 'high';
            }
            return $configured;
        }
        return $level === 'required' ? 'high' : ($level === 'preferred' ? 'medium' : 'low');
    }

    private function evaluateQualification($requirement, array $answers, JobVacancy $vacancy): array
    {
        $text = trim((string) ($requirement->qualification_text ?? ''));
        $type = $this->inferQualificationType((string) ($requirement->qualification_type ?? 'auto'), $text);
        $minimum = $requirement->minimum_value;
        $unit = $requirement->minimum_unit ?: null;

        if ($minimum === null && preg_match('/\b(?:at\s+least\s+|minimum(?:\s+of)?\s+)?(\d+(?:\.\d+)?)\s*(?:(?:-|–|to)\s*\d+(?:\.\d+)?\s*)?\+?\s*(years?|yrs?|months?|mos?)\b/iu', $text, $match)) {
            $minimum = (float) $match[1];
            $unit = str_starts_with(strtolower($match[2]), 'mo') ? 'months' : 'years';
        }

        if ($type === 'experience') {
            $workDeclaration = Str::lower(trim((string) ($answers['work_experience_declaration'] ?? '')));
            if ($workDeclaration === 'no') {
                return $this->qualificationResult(
                    $type,
                    'not_matched',
                    'The applicant explicitly reported having no previous or current work experience, so this experience requirement is not met.',
                    0,
                    'No work experience declared',
                    $minimum,
                    ['evidence_source' => 'Work Experience Declaration']
                );
            }

            $experience = $this->experienceEvidenceForQualification($answers, $text, $vacancy);
            if (!$experience['has_history']) {
                return $this->qualificationResult($type, 'not_evidenced', 'No employment history was submitted for this requirement.', null, 'No employment evidence', $minimum);
            }
            if (!$experience['verifiable']) {
                return $this->qualificationResult($type, 'not_evidenced', 'Employment history exists, but dates are incomplete or invalid, so the required duration cannot be verified.', null, 'Duration not verifiable', $minimum);
            }

            $months = $experience['relevant_months'];
            $requiredMonths = $minimum !== null
                ? ($unit === 'months' ? (int) round($minimum) : (int) round($minimum * 12))
                : null;

            if ($requiredMonths !== null) {
                $status = $months >= $requiredMonths ? 'matched' : 'not_matched';
            } else {
                $status = $months > 0 ? 'matched' : 'not_matched';
            }

            if ($status === 'not_matched' && $experience['incomplete_history']) {
                $status = 'needs_verification';
            }

            $evidence = $this->monthsLabel($months) . ' role-relevant experience'
                . ($requiredMonths !== null ? ' vs ' . $this->monthsLabel($requiredMonths) . ' required' : '')
                . ($experience['roles'] ? ' · positions: ' . implode('; ', $experience['roles']) : '')
                . ($experience['incomplete_history'] ? '. Some records have incomplete/invalid dates; these months are a supported lower bound.' : '');

            return $this->qualificationResult(
                $type,
                $status,
                $evidence,
                $months,
                $this->monthsLabel($months) . ' role-relevant experience',
                $requiredMonths,
                ['evidence_source' => 'Employment position titles + dates']
            );
        }

        if ($type === 'education') {
            $degree = trim((string) ($answers['college_course'] ?? ''));
            // V8 no longer asks a separate degree-completion question. Keep support
            // for historical V5 answers when they exist, but do not require that
            // removed field for new applications.
            $legacyCompletion = trim((string) ($answers['college_completion_status'] ?? ''));
            $requiresCompletedDegree = (bool) preg_match('/\b(bachelor(?:\'s)?|degree|graduate|graduated|college\s+graduate)\b/i', $text)
                && !preg_match('/\b(college\s+level|undergraduate|currently\s+enrolled|student)\b/i', $text);

            if ($degree === '') {
                if ($requiresCompletedDegree && Str::contains(Str::lower($legacyCompletion), 'no college')) {
                    return $this->qualificationResult(
                        $type,
                        'not_matched',
                        'The vacancy requires a completed college degree, while the applicant previously selected "No College / Not Applicable".',
                        0,
                        'No college degree declared',
                        null,
                        ['evidence_source' => 'Degree / Course']
                    );
                }
                return $this->qualificationResult(
                    $type,
                    'not_evidenced',
                    'Degree/course was not provided.',
                    null,
                    'Degree not provided',
                    null,
                    ['evidence_source' => 'Degree / Course']
                );
            }

            $requiredDisciplines = $this->educationDisciplineTags($text);
            $applicantDisciplines = $this->educationDisciplineTags($degree);
            $genericDegreeRequirement = preg_match('/\b(degree|bachelor|graduate|college)\b/i', $text)
                && $requiredDisciplines === [];

            $status = 'not_matched';
            $metric = 0;
            $relatedFieldMatch = false;
            if ($genericDegreeRequirement) {
                $status = 'matched';
                $metric = 1;
            } elseif ($requiredDisciplines !== []) {
                $matches = array_values(array_intersect($requiredDisciplines, $applicantDisciplines));
                if ($matches) {
                    $status = 'matched';
                    $metric = count($matches);
                } elseif (preg_match('/\brelated\s+field\b/i', $text)) {
                    if ($this->educationAdjacentMatch($requiredDisciplines, $applicantDisciplines)) {
                        // The vacancy explicitly accepts a related field and the
                        // declared discipline belongs to an adjacent discipline family
                        // (e.g. Computer Engineering for an IT/CS/IS requirement).
                        // This is a role-fit match; normal diploma/TOR verification is
                        // still a separate HR evidence-verification step.
                        $status = 'matched';
                        $metric = 1;
                        $relatedFieldMatch = true;
                    } elseif ($applicantDisciplines === []) {
                        $status = 'needs_verification';
                        $metric = 1;
                    }
                }
            } else {
                $overlap = $this->meaningfulOverlap($text, $degree, ['degree','bachelor','graduate','college','course','field','science','technology']);
                $status = $overlap >= 1 ? 'matched' : 'not_matched';
                $metric = $overlap;
            }

            if ($requiresCompletedDegree) {
                $legacyLower = Str::lower($legacyCompletion);
                $explicitIncomplete = Str::contains($legacyLower . ' ' . Str::lower($degree), [
                    'undergraduate', 'not completed', 'currently enrolled', 'no college',
                    'not graduated', 'incomplete', 'ongoing', 'on-going', 'college level',
                ]);
                $lowerDegreeLevel = preg_match('/\b(bachelor|baccalaureate)\b/i', $text)
                    && preg_match('/\b(associate|diploma|certificate)\b/i', $degree)
                    && !preg_match('/\b(bachelor|baccalaureate|master|doctor)\b/i', $degree);
                $explicitCompleted = Str::contains($legacyLower, ['graduated', 'degree completed']);
                $declaredDegreeLevel = (bool) preg_match(
                    '/\b(bachelor(?:\'s)?|bachelor\s+of|master(?:\'s)?|doctor(?:ate)?|associate(?:\'s)?|bsit|bscs|bsis|bsba|bsa|bsee|bsce|bsme|bsn|bsed|b\.?s\.?|b\.?a\.?)\b/i',
                    $degree
                );

                if ($explicitIncomplete || $lowerDegreeLevel) {
                    $status = 'not_matched';
                    $metric = 0;
                } elseif (!$explicitCompleted && $status === 'matched' && !$declaredDegreeLevel) {
                    // A generic course name such as "Information Technology" does
                    // not by itself establish that a required bachelor's degree was
                    // completed. Ask HR to verify instead of inventing a match.
                    $status = 'needs_verification';
                }
            }

            $evidence = 'Applicant-declared Degree / Course: ' . $degree . '. ';
            if ($legacyCompletion !== '') {
                $evidence .= 'Historical completion status: ' . $legacyCompletion . '. ';
            }
            $evidence .= match ($status) {
                'matched' => $relatedFieldMatch
                    ? 'The vacancy explicitly accepts a related field, and the declared discipline is in an adjacent discipline family. It is treated as a role-fit match while the applicant declaration remains subject to normal HR/document verification.'
                    : 'The declared degree/course aligns with the stated education requirement. Applicant declarations remain subject to HR/document verification.',
                'needs_verification' => 'The wording does not clearly establish the required degree level/completion or the discipline could not be confidently mapped to the vacancy. Verify before treating the requirement as met.',
                default => 'The declared discipline does not show a clear match to the stated education requirement.',
            };

            return $this->qualificationResult($type, $status, $evidence, $metric, $degree, null, [
                'evidence_source' => 'Applicant-declared Degree / Course',
                'related_field_match' => $relatedFieldMatch,
            ]);
        }

        if ($type === 'certification') {
            $bundle = $this->credentialEvidenceBundle($answers);
            $evidence = $bundle['all_text'];
            $declaredNo = $this->credentialRequirementExplicitlyDeclined($text, $bundle);
            if ($evidence === '') {
                return $this->qualificationResult(
                    $type,
                    $declaredNo ? 'not_matched' : 'not_evidenced',
                    $declaredNo
                        ? 'The applicant explicitly declared that they do not hold the type of credential required by this vacancy.'
                        : 'The concise public application no longer asks for certifications/licenses. No legacy credential evidence exists for this applicant, so HR should verify any required credential directly from the resume/documents or interview.',
                    $declaredNo ? 0 : null,
                    $declaredNo ? 'Required credential explicitly not held' : 'Credential verification required',
                    null,
                    ['evidence_source' => $declaredNo ? 'Legacy credential declaration' : 'Not captured by the concise public form']
                );
            }

            $requiredIds = $this->certificationIdentifiers($text);
            $requiresAllCredentials = count($requiredIds) > 1
                && preg_match('/\band\b/i', $text)
                && !preg_match('/\bor\b/i', $text);
            $requiresCurrentValidity = (bool) preg_match('/\b(license|licensed|licensure|registration|registered|active|valid|current)\b/i', $text);

            $validIds = $this->certificationIdentifiers($bundle['valid_text']);
            $pendingIds = $this->certificationIdentifiers($bundle['pending_text']);
            $expiredIds = $this->certificationIdentifiers($bundle['expired_text']);
            $unknownIds = $this->certificationIdentifiers($bundle['unknown_text']);
            $allIds = $this->certificationIdentifiers($evidence);

            $validMatches = array_values(array_intersect($requiredIds, $validIds));
            $pendingMatches = array_values(array_intersect($requiredIds, $pendingIds));
            $expiredMatches = array_values(array_intersect($requiredIds, $expiredIds));
            $unknownMatches = array_values(array_intersect($requiredIds, $unknownIds));
            $allMatches = array_values(array_intersect($requiredIds, $allIds));

            if ($requiredIds) {
                $allRequiredValid = count(array_unique($validMatches)) === count(array_unique($requiredIds));
                $anyUnverifiedRequired = (bool) array_intersect($requiredIds, array_unique(array_merge($pendingIds, $unknownIds)));
                $anyExpiredRequired = (bool) array_intersect($requiredIds, $expiredIds);

                if ((!$requiresAllCredentials && $validMatches) || ($requiresAllCredentials && $allRequiredValid)) {
                    $status = 'matched';
                    $reason = 'Valid/completed named credential evidence: ' . implode(', ', $validMatches ?: $requiredIds) . '.';
                    $metric = count($validMatches ?: $requiredIds) * 3;
                } elseif ($anyUnverifiedRequired) {
                    $status = 'needs_verification';
                    $unverified = array_values(array_unique(array_merge($pendingMatches, $unknownMatches)));
                    $reason = 'The required credential appears in the application, but its completion/current status is pending or not structured well enough to verify: ' . implode(', ', $unverified) . '.';
                    $metric = null;
                } elseif ($anyExpiredRequired) {
                    $status = $requiresCurrentValidity ? 'not_matched' : 'needs_verification';
                    $reason = $requiresCurrentValidity
                        ? 'The required credential is listed as expired and therefore does not satisfy the current/valid credential requirement: ' . implode(', ', $expiredMatches) . '.'
                        : 'A matching credential is listed as expired. HR should verify whether historical completion is sufficient for this vacancy: ' . implode(', ', $expiredMatches) . '.';
                    $metric = $status === 'not_matched' ? 0 : null;
                } elseif ($requiresAllCredentials && $allMatches) {
                    $missing = array_values(array_diff($requiredIds, $allMatches));
                    $status = 'not_evidenced';
                    $reason = 'Only part of the combined credential requirement is evidenced. Missing/unevidenced: ' . implode(', ', $missing) . '.';
                    $metric = null;
                } else {
                    $explicitNoCredential = $declaredNo || ((bool) preg_match('/\b(no|none|without|not\s+(?:a\s+)?(?:licensed|certified)|do\s+not\s+have|don\'t\s+have|wala(?:ng)?|hindi\s+(?:licensed|certified))\b/i', $evidence)
                        && count($this->matchingDistinctiveTerms($text, $evidence)) > 0);
                    $status = $explicitNoCredential ? 'not_matched' : 'not_evidenced';
                    $reason = $explicitNoCredential
                        ? 'The applicant explicitly indicated that the specifically required credential/license is not held.'
                        : 'The submitted credentials do not establish the specifically required credential (' . implode(', ', $requiredIds) . ').';
                    $metric = $explicitNoCredential ? 0 : null;
                }
            } else {
                $validOverlap = $this->meaningfulOverlap($text, $bundle['valid_text'], ['certification','certificate','certified','license','licensed','training']);
                $validPhrase = $this->phraseMatches($text, $bundle['valid_text']);
                $pendingOverlap = $this->meaningfulOverlap($text, trim($bundle['pending_text'] . ' ' . $bundle['unknown_text']), ['certification','certificate','certified','license','licensed','training']);
                $expiredOverlap = $this->meaningfulOverlap($text, $bundle['expired_text'], ['certification','certificate','certified','license','licensed','training']);

                if ($validOverlap > 0 || $validPhrase > 0) {
                    $status = 'matched';
                    $reason = 'A completed/valid structured credential directly aligns with the requirement.';
                    $metric = $validOverlap + ($validPhrase * 2);
                } elseif ($pendingOverlap > 0) {
                    $status = 'needs_verification';
                    $reason = 'A related credential/training is listed, but it is in progress, pending verification, or has no structured status.';
                    $metric = null;
                } elseif ($expiredOverlap > 0) {
                    $status = $requiresCurrentValidity ? 'not_matched' : 'needs_verification';
                    $reason = $requiresCurrentValidity
                        ? 'The related credential is expired and does not satisfy the stated current/valid requirement.'
                        : 'A related credential is expired; verify whether historical completion is acceptable for this vacancy.';
                    $metric = $status === 'not_matched' ? 0 : null;
                } else {
                    $status = $declaredNo ? 'not_matched' : 'not_evidenced';
                    $reason = $declaredNo
                        ? 'The applicant explicitly declared that they do not hold the type of credential required by this vacancy.'
                        : 'Credentials were provided, but none specifically establish this vacancy requirement.';
                    $metric = $declaredNo ? 0 : null;
                }
            }

            $summary = $bundle['record_summaries']
                ? implode('; ', array_slice($bundle['record_summaries'], 0, 4))
                : Str::limit($evidence, 200);

            return $this->qualificationResult(
                $type,
                $status,
                $reason . ' Submitted: ' . $summary,
                $metric,
                $status === 'matched'
                    ? (($validMatches ?? []) ? implode(', ', $validMatches) : 'Structured credential match')
                    : ($status === 'not_matched' ? 'Credential requirement not met' : 'Credential requires verification'),
                null,
                ['evidence_source' => 'Structured professional credentials / certifications']
            );
        }

        if ($type === 'availability') {
            $availability = trim((string) ($answers['availability'] ?? ''));
            $notice = trim((string) ($answers['notice_period'] ?? ''));
            $employmentStatus = trim((string) ($answers['current_employment_status'] ?? ''));
            $lowerRequirement = Str::lower($text);

            $isStartTimingRequirement = (bool) preg_match('/\b(start|available|availability|notice|immediate(?:ly)?|within\s+\d+\s+(?:day|days|week|weeks|month|months))\b/i', $lowerRequirement);
            if (!$isStartTimingRequirement) {
                return $this->qualificationResult(
                    $type,
                    'not_evidenced',
                    'This work-arrangement requirement is intentionally not asked in the concise application form. HR should verify it during interview if it is essential to the vacancy.',
                    null,
                    'Requirement not captured by concise form',
                    null,
                    ['evidence_source' => 'No matching fixed-form readiness field']
                );
            }

            if ($availability === '' && $notice === '') {
                return $this->qualificationResult(
                    $type,
                    'not_evidenced',
                    'No start-availability evidence was submitted.',
                    null,
                    'Availability not provided',
                    null,
                    ['evidence_source' => 'Availability / notice period']
                );
            }

            $metric = null;
            $requirement = null;
            if (Str::contains($lowerRequirement, ['immediate', 'immediately'])) {
                $requirement = 0;
                $metric = $this->availabilityDays($availability, $notice, '');
                $status = $metric === null ? 'needs_verification' : ($metric === 0 ? 'matched' : 'not_matched');
            } elseif (($maxDays = $this->availabilityMaximumDays($lowerRequirement)) !== null) {
                $requirement = $maxDays;
                $metric = $this->availabilityDays($availability, $notice, '');
                if ($metric === null) {
                    $status = 'needs_verification';
                } elseif (Str::contains(Str::lower($availability . ' ' . $notice), 'more than 30 days') && $maxDays > 30) {
                    $status = 'needs_verification';
                } else {
                    $status = $metric <= $maxDays ? 'matched' : 'not_matched';
                }
            } elseif (Str::contains(Str::lower($availability), 'to be discussed')) {
                $status = 'needs_verification';
            } else {
                $status = $availability !== '' ? 'matched' : 'not_evidenced';
            }

            $evidence = 'Availability: ' . ($availability ?: 'not provided')
                . ' · Current employment: ' . ($employmentStatus ?: 'not provided')
                . ' · Notice: ' . ($notice ?: 'not provided') . '.';

            return $this->qualificationResult(
                $type,
                $status,
                $evidence,
                $metric,
                $metric !== null ? 'Estimated start in ' . $metric . ' day(s)' : ($status === 'matched' ? 'Basic availability evidence supports the requirement' : 'Availability requires verification'),
                $requirement,
                ['evidence_source' => 'Basic availability / current employment / notice period']
            );
        }

        $compoundComponents = $this->compoundSkillComponents($text);
        if (count($compoundComponents) >= 2) {
            return $this->evaluateCompoundSkillQualification($text, $compoundComponents, $answers);
        }

        if ($this->isToolRequirement($text)) {
            return $this->evaluateToolQualification($text, $answers, $vacancy);
        }

        if ($competency = $this->behavioralCompetencyForRequirement($text)) {
            return $this->evaluateBehavioralQualification($text, $competency, $answers);
        }

        $source = $this->positiveQualificationEvidenceText($answers);
        $trainingGap = trim((string) ($answers['role_training_gap'] ?? ''));
        if ($source === '') {
            $status = $this->trainingGapMatchesRequirement($text, $trainingGap) ? 'needs_verification' : 'not_evidenced';
            return $this->qualificationResult(
                $type,
                $status,
                $status === 'needs_verification'
                    ? 'The applicant identified this area as a training/support need, but no positive evidence of proficiency was found.'
                    : 'No role-related evidence was submitted for this qualification.',
                null,
                $status === 'needs_verification' ? 'Training gap disclosed' : 'No assessable evidence',
                null,
                ['evidence_source' => $status === 'needs_verification' ? 'Training gap answer' : null]
            );
        }

        if ($this->explicitLackOfRequirement($text, $source . ' ' . $trainingGap)) {
            return $this->qualificationResult($type, 'not_matched', 'The applicant explicitly indicated no/insufficient experience with this requirement.', 0, 'Explicit lack of experience', null);
        }

        $overlapTerms = $this->matchingDistinctiveTerms($text, $source);
        $phraseMatches = $this->phraseMatches($text, $source);
        $concepts = $this->matchedSkillConcepts($text, $source);
        $actionSignals = $this->actionSignalCount($source);
        $strength = count($overlapTerms) + ($phraseMatches * 2) + (count($concepts) * 2);

        if ($phraseMatches > 0 || count($concepts) > 0 || count($overlapTerms) >= 2) {
            $status = 'matched';
        } elseif (count($overlapTerms) === 1 && $actionSignals > 0) {
            $status = 'needs_verification';
        } elseif ($this->trainingGapMatchesRequirement($text, $trainingGap)) {
            $status = 'needs_verification';
        } else {
            $status = 'not_evidenced';
        }

        $evidence = match ($status) {
            'matched' => 'Specific requirement evidence found: ' . implode(', ', array_slice(array_values(array_unique(array_merge($concepts, $overlapTerms))), 0, 8)) . '.',
            'needs_verification' => 'Only a partial/generic signal was found; HR should ask for a concrete example or proof of actual use.',
            default => 'The applicant submitted role evidence, but no sufficiently specific evidence for this qualification was found.',
        };

        return $this->qualificationResult(
            $type,
            $status,
            $evidence,
            $strength ?: null,
            $strength ? $strength . ' specific evidence signal(s)' : 'No specific signal',
            null,
            ['evidence_source' => 'Role-focused questionnaire']
        );
    }

    private function qualificationResult(string $type, string $status, string $evidence, $metric, string $metricLabel, $requirementValue, array $extra = []): array
    {
        return array_merge([
            'type' => $type,
            'status' => $status,
            'evidence' => $evidence,
            'metric' => $metric,
            'metric_label' => $metricLabel,
            'requirement_value' => $requirementValue,
        ], $extra);
    }

    private function inferQualificationType(string $configured, string $text): string
    {
        $lower = Str::lower($text);

        // Repair legacy metadata produced by the previous parser. A phrase such as
        // "experience using NetSuite/QuickBooks" is a skill/tool requirement unless
        // it also contains an explicit duration. This correction applies even when an
        // older database row was already saved as qualification_type=experience.
        $toolOnlyExperience = preg_match('/\b(experience\s+using|proficien|knowledge\s+of|familiar\s+with|software|system|tool|netsuite|quickbooks|excel)\b/i', $text)
            && !preg_match('/\b\d+(?:\.\d+)?\s*(years?|yrs?|months?|mos?)\b/i', $text);
        if ($configured === 'experience' && $toolOnlyExperience) {
            return 'skill';
        }

        if ($configured !== '' && $configured !== 'auto') {
            return $configured;
        }

        if (Str::contains($lower, ['degree','graduate','bachelor','college','course','diploma','accountancy','accounting technology','information technology','engineering'])) {
            return 'education';
        }
        if (Str::contains($lower, ['certif','license','licensed','licensure','nc ii','nc2'])) {
            return 'certification';
        }
        if (Str::contains($lower, ['available','availability','start immediately','onsite','on-site','willing to work','shift schedule'])) {
            return 'availability';
        }

        // "Experience using NetSuite/QuickBooks" is a tool/skill requirement, not a
        // duration requirement. Explicit duration or role-history wording stays experience.
        if ($toolOnlyExperience) {
            return 'skill';
        }
        if (preg_match('/\b(year|years|yr|yrs|month|months|experience as|experience in|worked as|work experience)\b/i', $text)) {
            return 'experience';
        }

        return 'skill';
    }

    private function experienceEvidenceForQualification(array $answers, string $requirementText, JobVacancy $vacancy): array
    {
        $verifiable = false;
        $incompleteHistory = false;
        $hasHistory = false;
        $roles = [];
        $totalIntervals = [];
        $relevantIntervals = [];
        $requirementTerms = $this->qualificationSpecificTerms($requirementText, [
            'year','years','yr','yrs','month','months','experience','experienced','minimum','least','similar','role',
        ]);
        $roleTerms = $this->experienceRoleTerms($vacancy);
        $experienceContext = $this->experienceRoleContext($vacancy) . ' ' . $requirementText;

        for ($i = 1; $i <= 5; $i++) {
            $position = trim((string) ($answers["position_held_{$i}"] ?? ''));
            $startValue = trim((string) ($answers["employment_dates_{$i}"] ?? ''));
            $endValue = trim((string) ($answers["employment_end_{$i}"] ?? ''));
            $current = Str::lower(trim((string) ($answers["currently_employed_{$i}"] ?? ''))) === 'yes';

            if ($position === '' && $startValue === '' && $endValue === '') {
                continue;
            }

            $hasHistory = true;
            if ($position !== '') {
                $roles[] = $position;
            }

            $start = $this->parseDate($startValue);
            $end = $current ? now() : $this->parseDate($endValue);
            if (!$start || !$end || $end->lt($start) || $start->gt(now()->endOfDay()) || $end->gt(now()->endOfDay())) {
                $incompleteHistory = true;
                continue;
            }

            $verifiable = true;
            $totalIntervals[] = [$start, $end];

            // Assessment V8 intentionally uses the employer role title plus dates
            // for employment-history relevance. Applicants provide detailed skills,
            // tools, projects, and results once in the Role-Specific Assessment.
            $jobText = $position;
            $jobTerms = $this->terms($jobText);
            $reqOverlap = count(array_intersect($jobTerms, $requirementTerms));
            $roleOverlap = count(array_intersect($jobTerms, $roleTerms));
            $rolePhrase = $this->phraseMatches($requirementText . ' ' . ($vacancy->title ?? ''), $jobText);
            $conceptMatches = count($this->matchedSkillConcepts($experienceContext, $jobText));
            $titleFamilySignals = $this->roleTitleFamilySignals($experienceContext, $jobText);

            if ($reqOverlap > 0 || $roleOverlap > 0 || $rolePhrase > 0 || $conceptMatches > 0 || $titleFamilySignals > 0) {
                $relevantIntervals[] = [$start, $end];
            }
        }

        $total = $this->mergedIntervalMonths($totalIntervals);
        $relevant = $this->mergedIntervalMonths($relevantIntervals);

        // If the qualification only states a generic amount of experience and does
        // not name a discipline/role, total date-supported experience is acceptable.
        $hasSpecificRoleTerms = count($requirementTerms) > 0;
        $relevantMonths = $hasSpecificRoleTerms ? $relevant : $total;

        return [
            'total_months' => $total,
            'relevant_months' => $relevantMonths,
            'verifiable' => $verifiable,
            'incomplete_history' => $incompleteHistory,
            'has_history' => $hasHistory,
            'roles' => array_slice(array_values(array_unique($roles)), 0, 5),
        ];
    }

    private function positiveQualificationEvidenceText(array $answers): string
    {
        // New applications provide detailed competency evidence once in the
        // Role-Specific Assessment. This avoids over-weighting applicants who repeat
        // the same claim across multiple employer records.
        $keys = [
            'role_relevant_skills','role_tools_systems','role_strongest_requirement','role_similar_project',
            'role_problem_solving','role_problem_action','role_problem_result',
        ];

        return trim(implode(' ', array_filter(array_map(
            fn ($key) => trim((string) ($answers[$key] ?? '')),
            $keys
        ))));
    }

    private function toolUsageEvidenceText(array $answers): string
    {
        $keys = [
            'role_tools_systems','role_similar_project','role_relevant_skills',
            'role_strongest_requirement','role_problem_action','role_problem_result',
        ];

        return trim(implode(' ', array_filter(array_map(
            fn ($key) => trim((string) ($answers[$key] ?? '')),
            $keys
        ))));
    }

    private function directToolUsageEvidenceText(array $answers): string
    {
        $keys = [
            'role_tools_systems','role_similar_project','role_problem_action','role_problem_result',
        ];

        return trim(implode(' ', array_filter(array_map(
            fn ($key) => trim((string) ($answers[$key] ?? '')),
            $keys
        ))));
    }

    private function qualificationEvidenceText(array $answers): string
    {
        return $this->positiveQualificationEvidenceText($answers);
    }

    private function experienceAssessment(array $answers, JobVacancy $vacancy): ?array
    {
        $jobs = [];
        for ($i = 1; $i <= 5; $i++) {
            $company = trim((string) ($answers["company_{$i}"] ?? ''));
            $address = trim((string) ($answers["company_address_{$i}"] ?? ''));
            $position = trim((string) ($answers["position_held_{$i}"] ?? ''));
            $startValue = trim((string) ($answers["employment_dates_{$i}"] ?? ''));
            $endValue = trim((string) ($answers["employment_end_{$i}"] ?? ''));
            $current = Str::lower(trim((string) ($answers["currently_employed_{$i}"] ?? ''))) === 'yes';

            if ($company === '' && $address === '' && $position === '' && $startValue === '' && $endValue === '') {
                continue;
            }

            $jobs[] = compact('company', 'address', 'position', 'startValue', 'endValue', 'current');
        }

        if (!$jobs) {
            if (Str::lower(trim((string) ($answers['work_experience_declaration'] ?? ''))) === 'no') {
                return [
                    'score' => 30,
                    'coverage' => 100,
                    'detail' => 'The applicant explicitly reported having no previous or current work experience. Any minimum-experience requirement is handled separately in Vacancy Qualification Match.',
                    'evidence_items' => ['No work experience declared'],
                    'comparison' => [
                        'metric' => 30,
                        'primary' => 'No work experience declared',
                        'secondary' => 'Applicant explicitly selected No',
                        'facts' => [
                            ['label' => 'Work experience declaration', 'value' => 'No'],
                            ['label' => 'Employment records', 'value' => '0'],
                        ],
                    ],
                ];
            }
            return null;
        }

        $roleTerms = $this->experienceRoleTerms($vacancy);
        $experienceContext = $this->experienceRoleContext($vacancy);
        $totalIntervals = [];
        $relevantIntervals = [];
        $verifiableJobs = 0;
        $relevantJobs = 0;
        $bestAlignment = 0;
        $titles = [];
        $recordEvidence = [];
        $coverageParts = 0;
        $coverageDone = 0;

        foreach ($jobs as $job) {
            $jobText = $job['position'];
            $overlap = count(array_intersect($this->terms($jobText), $roleTerms));
            $phrase = $this->phraseMatches($experienceContext, $jobText);
            $concepts = count($this->matchedSkillConcepts($experienceContext, $jobText));
            $titleFamilySignals = $this->roleTitleFamilySignals($experienceContext, $jobText);
            $alignment = $overlap + ($phrase * 2) + ($concepts * 2) + ($titleFamilySignals * 3);
            if ($job['position'] !== '') {
                $titles[] = $job['position'];
            }

            foreach (['company','address','position','startValue'] as $part) {
                $coverageParts++;
                if (trim((string) $job[$part]) !== '') {
                    $coverageDone++;
                }
            }
            $coverageParts++;
            if ($job['current'] || trim((string) $job['endValue']) !== '') {
                $coverageDone++;
            }

            $start = $this->parseDate($job['startValue']);
            $end = $job['current'] ? now() : $this->parseDate($job['endValue']);
            if (!$start || !$end || $end->lt($start) || $start->gt(now()->endOfDay()) || $end->gt(now()->endOfDay())) {
                continue;
            }

            $bestAlignment = max($bestAlignment, $alignment);

            if ($alignment > 0) {
                $relevantJobs++;
            }

            $verifiableJobs++;
            $totalIntervals[] = [$start, $end];
            if ($alignment > 0) {
                $relevantIntervals[] = [$start, $end];
            }
            $recordEvidence[] = trim(implode(' · ', array_filter([
                $job['position'],
                $job['company'],
                $start->format('M Y') . ' - ' . ($job['current'] ? 'Present' : $end->format('M Y')),
            ])));
        }

        $coverage = $coverageParts > 0 ? (int) round(($coverageDone / $coverageParts) * 100) : 0;
        $coverage = min($coverage, (int) round(100 * $verifiableJobs / count($jobs)));
        $totalMonths = $this->mergedIntervalMonths($totalIntervals);
        $relevantMonths = $this->mergedIntervalMonths($relevantIntervals);
        $requiredMonths = $this->requiredExperienceMonths($vacancy);

        if ($verifiableJobs === 0) {
            return [
                'score' => null,
                'coverage' => 0,
                'detail' => 'Employment records were submitted, but the dates are incomplete or invalid. Duration and role-relevant tenure cannot be assessed reliably.',
                'evidence_items' => array_slice(array_values(array_unique($titles)), 0, 5),
                'comparison' => [
                    'metric' => null,
                    'primary' => 'Employment duration not assessable',
                    'secondary' => count($jobs) . ' submitted record(s)',
                    'facts' => [
                        ['label' => 'Employment records', 'value' => (string) count($jobs)],
                        ['label' => 'Date-supported records', 'value' => '0'],
                        ['label' => 'Positions submitted', 'value' => $titles ? implode(', ', array_slice(array_values(array_unique($titles)), 0, 5)) : 'Not provided'],
                    ],
                ],
            ];
        }

        if ($requiredMonths !== null && $requiredMonths > 0) {
            $ratio = $relevantMonths / $requiredMonths;
            if ($ratio < 1) {
                $durationScore = (int) round(35 + (max(0, $ratio) * 35));
            } else {
                $durationScore = (int) round(min(96, 70 + (($ratio - 1) * 18)));
            }
        } else {
            $durationScore = $relevantMonths >= 60 ? 96
                : ($relevantMonths >= 36 ? 90
                    : ($relevantMonths >= 24 ? 84
                        : ($relevantMonths >= 12 ? 76
                            : ($relevantMonths >= 6 ? 66
                                : ($relevantMonths > 0 ? 56 : 35)))));
        }

        $relevanceScore = $bestAlignment >= 7 ? 96
            : ($bestAlignment >= 5 ? 90
                : ($bestAlignment >= 3 ? 82
                    : ($bestAlignment >= 2 ? 72
                        : ($bestAlignment === 1 ? 60 : 32))));

        $score = (int) round(($durationScore * .45) + ($relevanceScore * .55));
        if ($relevantMonths === 0 && $bestAlignment === 0) {
            $score = min($score, 38);
        }
        if ($coverage < 70) {
            $score = min($score, 72);
        }

        $detail = $this->monthsLabel($totalMonths) . ' total date-supported experience · '
            . $this->monthsLabel($relevantMonths) . ' role-relevant by position title · '
            . $relevantJobs . '/' . count($jobs) . ' role-related job record(s)';
        if ($requiredMonths !== null) {
            $detail .= ' · vacancy minimum ' . $this->monthsLabel($requiredMonths) . ' (minimum itself is scored in Qualification Match)';
        }
        $detail .= '. Skills, tools, projects, and results are intentionally assessed from the Role-Specific Assessment to avoid duplicate evidence.';

        return [
            'score' => $score,
            'coverage' => $coverage,
            'detail' => $detail,
            'evidence_items' => array_slice($recordEvidence, 0, 5),
            'comparison' => [
                'metric' => $score,
                'primary' => $this->monthsLabel($relevantMonths) . ' role-relevant',
                'secondary' => $this->monthsLabel($totalMonths) . ' total · ' . $relevantJobs . '/' . count($jobs) . ' role-related record(s)',
                'facts' => [
                    ['label' => 'Role-relevant experience', 'value' => $this->monthsLabel($relevantMonths)],
                    ['label' => 'Total date-supported experience', 'value' => $this->monthsLabel($totalMonths)],
                    ['label' => 'Employment records used', 'value' => $verifiableJobs . '/' . count($jobs)],
                    ['label' => 'Role-related job records', 'value' => $relevantJobs . '/' . count($jobs)],
                    ['label' => 'Vacancy minimum', 'value' => $requiredMonths !== null ? $this->monthsLabel($requiredMonths) : 'Not explicitly specified'],
                    ['label' => 'Positions used', 'value' => $titles ? implode(', ', array_slice(array_values(array_unique($titles)), 0, 5)) : 'Not provided'],
                ],
            ],
        ];
    }

    private function skillsAssessment(array $answers, JobVacancy $vacancy): ?array
    {
        $keys = [
            'role_relevant_skills','role_tools_systems','role_strongest_requirement','role_similar_project',
        ];
        $values = $this->presentValues($answers, $keys);
        if (!$values) {
            return null;
        }

        $evidence = implode(' ', $values);

        // V9: Role-Specific Skills is a Role Fit component, so only the role title
        // and REQUIRED vacancy qualifications may influence its numeric score.
        // Preferred/nice-to-have items are still detected, but are displayed only as
        // optional advantages/tie-breakers under Vacancy Qualification Match.
        $context = $this->roleFitContext($vacancy);
        $optionalContext = $this->optionalRoleContext($vacancy);
        $roleTerms = $this->roleSpecificTerms($vacancy);
        $evidenceTerms = $this->terms($evidence);
        $matchedRoleTerms = array_values(array_intersect($roleTerms, $evidenceTerms));
        $phrases = $this->phraseMatches($context, $evidence);
        $matchedConcepts = $this->matchedSkillConcepts($context, $evidence);
        $vacancyTools = $this->toolNamesInText($context);
        $evidenceTools = $this->toolNamesInText($this->toolUsageEvidenceText($answers));
        $matchedTools = $this->relevantToolMatches($vacancyTools, $evidenceTools, $context);

        $optionalConcepts = $optionalContext !== ''
            ? array_values(array_diff($this->matchedSkillConcepts($optionalContext, $evidence), $matchedConcepts))
            : [];
        $optionalVacancyTools = $optionalContext !== '' ? $this->toolNamesInText($optionalContext) : [];
        $optionalMatchedTools = $optionalContext !== ''
            ? array_values(array_diff(
                $this->relevantToolMatches($optionalVacancyTools, $evidenceTools, $optionalContext),
                $matchedTools
            ))
            : [];

        $applicationEvidence = trim(implode(' ', array_filter([
            $answers['role_similar_project'] ?? '',
            $answers['role_strongest_requirement'] ?? '',
        ])));
        $actionSignals = $this->actionSignalCount($applicationEvidence);
        $resultSignals = $this->resultSignalCount($applicationEvidence);

        $coverage = 0;
        if (trim((string) ($answers['role_relevant_skills'] ?? '')) !== '') $coverage += 25;
        if (trim((string) ($answers['role_tools_systems'] ?? '')) !== '') $coverage += 25;
        if (trim((string) ($answers['role_similar_project'] ?? '')) !== '') $coverage += 30;
        if (trim((string) ($answers['role_strongest_requirement'] ?? '')) !== '') $coverage += 20;
        $coverage = min(100, $coverage);

        // V8 scores canonical competencies and named tools, not raw keyword hits.
        // A repeated word such as "Windows" must not be counted several times as
        // a term, concept, phrase, and tool just because it appears in multiple answers.
        $canonicalCompetencies = array_values(array_unique($matchedConcepts));
        $directSignals = count($canonicalCompetencies) + count($matchedTools);
        $alignmentScore = min(100,
            25
            + min(48, count($canonicalCompetencies) * 8)
            + min(35, count($matchedTools) * 7)
            + min(10, $phrases * 2)
        );
        $applicationScore = min(100,
            35
            + min(28, $actionSignals * 7)
            + min(24, $resultSignals * 8)
            + (trim((string) ($answers['role_similar_project'] ?? '')) !== '' ? 10 : 0)
        );
        $score = (int) round(($alignmentScore * .72) + ($applicationScore * .28));

        if ($directSignals === 0) {
            $score = min($score, 40);
        } elseif ($directSignals === 1 && $actionSignals === 0 && $resultSignals === 0) {
            $score = min($score, 56);
        }
        if ($coverage < 50) {
            $score = min($score, 62);
        }

        $matchedLabels = $canonicalCompetencies;
        $submittedTools = $this->toolNamesInText(trim((string) ($answers['role_tools_systems'] ?? '')));
        $toolLabel = $matchedTools
            ? implode(', ', $matchedTools)
            : ($vacancyTools ? 'No vacancy-required named tool evidenced' : 'No named tool explicitly required by vacancy');

        return [
            'score' => $score,
            'coverage' => $coverage,
            'detail' => count($matchedLabels) . ' required-baseline competency match(es) · '
                . count($matchedTools) . ' required-baseline named tool/system match(es) · '
                . ($actionSignals + $resultSignals) . ' applied-evidence signal(s). Optional/preferred evidence is displayed separately and does not inflate this Role Fit score.',
            'evidence_items' => array_keys($values),
            'comparison' => [
                'metric' => $score,
                'primary' => count($matchedLabels) . ' required competencies + ' . count($matchedTools) . ' required tool/system match(es)',
                'secondary' => $coverage . '% role-evidence coverage · ' . ($actionSignals + $resultSignals) . ' applied signal(s)',
                'facts' => [
                    ['label' => 'Matched required competencies', 'value' => $matchedLabels ? implode(', ', array_slice($matchedLabels, 0, 8)) : 'No specific required vacancy competency detected'],
                    ['label' => 'Exact tools/systems submitted', 'value' => $submittedTools ? implode(', ', array_slice($submittedTools, 0, 8)) : 'None specifically listed'],
                    ['label' => 'Matched required tools/systems', 'value' => $toolLabel],
                    ['label' => 'Optional competencies (not scored)', 'value' => $optionalConcepts ? implode(', ', array_slice($optionalConcepts, 0, 8)) : 'None additional'],
                    ['label' => 'Optional tools/systems (not scored)', 'value' => $optionalMatchedTools ? implode(', ', array_slice($optionalMatchedTools, 0, 8)) : 'None additional'],
                    ['label' => 'Evidence sources', 'value' => implode(', ', array_map(fn ($key) => Str::headline(str_replace('_', ' ', $key)), array_keys($values)))],
                    ['label' => 'Applied action/result signals', 'value' => (string) ($actionSignals + $resultSignals)],
                ],
            ],
        ];
    }

    private function problemSolvingAssessment(array $answers, JobVacancy $vacancy): ?array
    {
        $situation = trim((string) ($answers['role_problem_solving'] ?? ''));
        $action = trim((string) ($answers['role_problem_action'] ?? ''));
        $result = trim((string) ($answers['role_problem_result'] ?? ''));
        if ($situation === '' && $action === '' && $result === '') {
            return null;
        }

        $structured = $action !== '' || $result !== '';
        if (!$structured) {
            // Backward compatibility for applications submitted before V5 split the
            // problem answer into Situation / Action / Result fields.
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($situation)));
            $words = str_word_count($plain);
            $situationSignals = $this->situationSignalCount($plain);
            $actionSignals = $this->actionSignalCount($plain);
            $resultSignals = $this->resultSignalCount($plain);
            $roleContextLabels = $this->problemRoleContextEvidenceLabels($vacancy, $plain);
            $roleOverlap = count($roleContextLabels);

            $coverage = 0;
            if ($situationSignals > 0) {
                $coverage += 25;
            } elseif ($words >= 20) {
                $coverage += 10;
            }
            if ($actionSignals > 0) {
                $coverage += 40;
            }
            if ($resultSignals > 0) {
                $coverage += 35;
            }

            $situationScore = $situationSignals > 0 ? min(100, 75 + ($situationSignals * 10)) : ($words >= 20 ? 55 : 35);
            $actionScore = $actionSignals > 0 ? min(100, 65 + ($actionSignals * 12)) : 25;
            $resultScore = $resultSignals > 0 ? min(100, 60 + ($resultSignals * 15)) : 20;
            $roleScore = min(100, 50 + ($roleOverlap * 10));
            $substanceScore = $words >= 35 ? 90 : ($words >= 20 ? 78 : ($words >= 10 ? 62 : 45));

            $score = (int) round(
                ($situationScore * .15)
                + ($actionScore * .40)
                + ($resultScore * .30)
                + ($roleScore * .05)
                + ($substanceScore * .10)
            );
            if ($actionSignals === 0) {
                $score = min($score, 55);
            } elseif ($resultSignals === 0) {
                $score = min($score, 68);
            }
            if ($situationSignals === 0 && $actionSignals === 0 && $resultSignals === 0) {
                $score = min($score, 45);
            }

            return [
                'score' => $score,
                'coverage' => min(100, $coverage),
                'detail' => 'Legacy combined answer · Situation ' . ($situationSignals > 0 ? 'detected' : 'weak/unclear')
                    . ' · Action ' . ($actionSignals > 0 ? 'detected' : 'not detected')
                    . ' · Result ' . ($resultSignals > 0 ? 'detected' : 'not detected') . ' · ' . $words . ' words.',
                'evidence_items' => ['Legacy combined problem-solving answer'],
                'comparison' => [
                    'metric' => $score,
                    'primary' => ($actionSignals > 0 ? 'Action evidenced' : 'Action not evidenced') . ' / ' . ($resultSignals > 0 ? 'Result evidenced' : 'Result not evidenced'),
                    'secondary' => $coverage . '% legacy SAR evidence coverage · ' . $roleOverlap . ' role-context match(es)',
                    'facts' => [
                        ['label' => 'Legacy combined answer', 'value' => Str::limit($plain, 260, '...')],
                        ['label' => 'Role-context evidence', 'value' => $roleContextLabels ? implode(', ', $roleContextLabels) : 'No required-role concept directly evidenced'],
                        ['label' => 'Action signals', 'value' => (string) $actionSignals],
                        ['label' => 'Result signals', 'value' => (string) $resultSignals],
                    ],
                ],
            ];
        }

        $situationPlain = trim(preg_replace('/\s+/', ' ', strip_tags($situation)));
        $actionPlain = trim(preg_replace('/\s+/', ' ', strip_tags($action)));
        $resultPlain = trim(preg_replace('/\s+/', ' ', strip_tags($result)));
        $combined = trim($situationPlain . ' ' . $actionPlain . ' ' . $resultPlain);
        $situationWords = str_word_count($situationPlain);
        $actionWords = str_word_count($actionPlain);
        $resultWords = str_word_count($resultPlain);
        $actionSignals = $this->actionSignalCount($actionPlain);
        $resultSignals = $this->resultSignalCount($resultPlain);
        $situationSignals = $this->situationSignalCount($situationPlain);
        $roleContextLabels = $this->problemRoleContextEvidenceLabels($vacancy, $combined);
        $roleOverlap = count($roleContextLabels);
        $measurableResult = $this->measurableEvidenceCount($resultPlain) > 0;
        $resultContextSignals = $this->resultOutcomeContextCount($resultPlain);
        $emptyOutcome = (bool) preg_match('/^(?:none|n\/a|no\s+result|wala|walang\s+resulta|not\s+applicable)\.?$/i', trim($resultPlain));

        $situationScore = $situationWords >= 12 ? 88 : ($situationWords >= 7 ? 78 : ($situationWords >= 4 ? 62 : ($situationWords > 0 ? 45 : 0)));
        $situationScore = min(100, $situationScore + min(10, $situationSignals * 5));

        $actionScore = $actionWords >= 15 ? 88 : ($actionWords >= 8 ? 78 : ($actionWords >= 4 ? 60 : ($actionWords > 0 ? 42 : 0)));
        $actionScore = min(100, $actionScore + min(12, $actionSignals * 4));

        if ($emptyOutcome) {
            $resultScore = 20;
        } else {
            $resultScore = $resultWords >= 12 ? 84 : ($resultWords >= 6 ? 72 : ($resultWords >= 3 ? 55 : ($resultWords > 0 ? 38 : 0)));
            $resultScore = min(100, $resultScore
                + min(12, $resultSignals * 4)
                + min(10, $resultContextSignals * 5)
                + ($measurableResult ? 8 : 0));
        }

        $roleScore = min(100, 50 + ($roleOverlap * 10));
        $specificity = min(100, 42
            + min(20, $actionSignals * 5)
            + min(18, $resultSignals * 6)
            + min(12, $resultContextSignals * 4)
            + ($measurableResult ? 8 : 0));

        $coverage = 0;
        if ($situationWords >= 4) {
            $coverage += 20;
        } elseif ($situationWords > 0) {
            $coverage += 10;
        }
        if ($actionWords >= 4) {
            $coverage += 40;
        } elseif ($actionWords > 0) {
            $coverage += 20;
        }
        if ($resultWords >= 3 && !$emptyOutcome) {
            $coverage += 40;
        } elseif ($resultWords > 0) {
            $coverage += 20;
        }

        $score = (int) round(
            ($situationScore * .15)
            + ($actionScore * .40)
            + ($resultScore * .35)
            + ($roleScore * .05)
            + ($specificity * .05)
        );
        if ($actionWords === 0) {
            $score = min($score, 45);
        }
        if ($resultWords === 0 || $emptyOutcome) {
            $score = min($score, 62);
        }
        if ($actionWords < 4) {
            $score = min($score, 58);
        }
        // A strong qualitative outcome can score highly, but near-perfect problem-solving
        // evidence should require an objectively bounded/measurable result. This keeps a
        // well-written self-report from looking more certain than the evidence supports.
        if (!$measurableResult && $resultWords > 0 && !$emptyOutcome) {
            $score = min($score, $resultContextSignals >= 2 ? 94 : 92);
        }

        $actionLabel = $actionWords >= 4 ? 'Provided' : ($actionWords > 0 ? 'Too brief' : 'Missing');
        $resultLabel = $resultWords >= 3 && !$emptyOutcome ? 'Provided' : ($resultWords > 0 ? 'Weak / unclear' : 'Missing');

        return [
            'score' => max(0, min(100, $score)),
            'coverage' => min(100, $coverage),
            'detail' => 'Structured Situation–Action–Result evidence · Action ' . strtolower($actionLabel)
                . ' · Result ' . strtolower($resultLabel)
                . ($measurableResult ? ' · measurable outcome present'
                    : ($resultContextSignals > 0 ? ' · concrete outcome qualifier present' : ' · outcome specificity is limited')) . '.',
            'evidence_items' => array_values(array_filter(['Problem / Situation', $action !== '' ? 'Action' : null, $result !== '' ? 'Result' : null])),
            'comparison' => [
                'metric' => max(0, min(100, $score)),
                'primary' => 'Action ' . strtolower($actionLabel) . ' / Result ' . strtolower($resultLabel),
                'secondary' => $coverage . '% structured SAR coverage · ' . $roleOverlap . ' role-context match(es)',
                'facts' => [
                    ['label' => 'Situation', 'value' => $situationPlain !== '' ? Str::limit($situationPlain, 190, '...') : 'Not provided'],
                    ['label' => 'Action', 'value' => $actionPlain !== '' ? Str::limit($actionPlain, 190, '...') : 'Not provided'],
                    ['label' => 'Result', 'value' => $resultPlain !== '' ? Str::limit($resultPlain, 190, '...') : 'Not provided'],
                    ['label' => 'Role-context evidence', 'value' => $roleContextLabels ? implode(', ', $roleContextLabels) : 'No required-role concept directly evidenced'],
                    ['label' => 'Action specificity', 'value' => $actionSignals . ' action signal(s)'],
                    ['label' => 'Result specificity', 'value' => $resultSignals . ' outcome signal(s) · ' . $resultContextSignals . ' concrete qualifier(s)' . ($measurableResult ? ' · measurable value present' : '')],
                ],
            ],
        ];
    }

    /**
     * Return unique, explainable required-role concepts that are directly evidenced in
     * a problem-solving answer. This deliberately uses only required vacancy context:
     * preferred/nice-to-have tools may be shown as tie-breakers elsewhere but cannot
     * inflate Problem Solving Role Fit.
     *
     * @return array<int,string>
     */
    private function problemRoleContextEvidenceLabels(JobVacancy $vacancy, string $evidence): array
    {
        $evidence = trim(strip_tags($evidence));
        if ($evidence === '') {
            return [];
        }

        $requiredRequirements = $this->qualificationRequirements($vacancy)
            ->filter(function ($requirement) {
                $text = trim((string) ($requirement->qualification_text ?? ''));
                return $text !== '' && $this->effectiveRequirementLevel(
                    (string) ($requirement->requirement_level ?? ''),
                    $text
                ) === 'required';
            })
            ->values();

        $labels = [];
        $semanticKey = static function (string $label, string $fallback): string {
            $lower = Str::lower($label);
            if (Str::contains($lower, 'network')) return 'networking';
            if (Str::contains($lower, 'document')) return 'documentation';
            if (Str::contains($lower, 'windows')) return 'windows_support';
            if (Str::contains($lower, 'troubleshoot') || Str::contains($lower, 'problem solving')) return 'troubleshooting';
            if (Str::contains($lower, 'hardware') || Str::contains($lower, 'endpoint')) return 'hardware_support';
            if (Str::contains($lower, 'user support') || Str::contains($lower, 'help desk')) return 'user_support';
            if (Str::contains($lower, 'system administration')) return 'system_administration';
            return $fallback;
        };
        $add = static function (array &$rows, string $key, string $label) use ($semanticKey): void {
            $key = $semanticKey($label, $key);
            if ($label !== '' && !array_key_exists($key, $rows)) {
                $rows[$key] = $label;
            }
        };

        foreach ($requiredRequirements as $requirement) {
            $text = trim((string) ($requirement->qualification_text ?? ''));

            // Canonical concepts cover common business/technical role language while
            // remaining tied to a required qualification on both sides.
            foreach ($this->matchedSkillConcepts($text, $evidence) as $label) {
                $add($labels, 'concept:' . Str::slug($label), $label);
            }

            // Compound requirements are evaluated component-by-component so natural
            // evidence such as IP configuration/DHCP/gateway can satisfy Networking
            // even when the applicant never repeats the exact word "networking".
            foreach ($this->compoundSkillComponents($text) as $component) {
                $evaluation = $this->evaluateSkillComponent(
                    $component['key'],
                    $component['label'],
                    $evidence,
                    ''
                );
                if (($evaluation['status'] ?? '') === 'matched') {
                    $add($labels, 'component:' . $component['key'], $component['label']);
                }
            }

            // Named tools only count when that tool is part of a required qualification.
            // This protects the 5% role-context factor from optional/preferred leakage.
            $requiredTools = $this->toolNamesInText($text);
            if ($requiredTools) {
                $evidenceTools = $this->toolNamesInText($evidence);
                foreach ($this->relevantToolMatches($requiredTools, $evidenceTools, $text) as $tool) {
                    $add($labels, 'tool:' . Str::lower($tool), $tool);
                }
            }
        }

        // Legacy vacancies may have no structured required rows. Fall back to the
        // required role-fit context, still using conservative distinctive terms.
        if ($requiredRequirements->isEmpty()) {
            $context = $this->roleFitContext($vacancy);
            foreach ($this->matchedSkillConcepts($context, $evidence) as $label) {
                $add($labels, 'concept:' . Str::slug($label), $label);
            }
            foreach (array_intersect($this->roleSpecificTerms($vacancy), $this->terms($evidence)) as $term) {
                $add($labels, 'term:' . $term, Str::headline($term));
            }
        }

        // Five unique context concepts are enough to fully saturate the small (5%)
        // context component; keeping the list concise also makes the UI readable.
        return array_values(array_slice($labels, 0, 5, true));
    }

    private function roleEvidenceQualityAssessment(array $answers, JobVacancy $vacancy): ?array
    {
        // Four semantic evidence dimensions. V8 combines related role-focused fields so the
        // evidence-quality score is not distorted merely because the form was made more
        // structured than the legacy four-textarea version.
        $dimensions = [
            'Role skills / tools' => trim(implode(' ', array_filter([
                $answers['role_relevant_skills'] ?? '',
                $answers['role_tools_systems'] ?? '',
            ]))),
            'Similar work example' => trim((string) ($answers['role_similar_project'] ?? '')),
            'Problem-solving SAR' => trim(implode(' ', array_filter([
                $answers['role_problem_solving'] ?? '',
                $answers['role_problem_action'] ?? '',
                $answers['role_problem_result'] ?? '',
            ]))),
            'Strongest requirement proof' => trim((string) ($answers['role_strongest_requirement'] ?? '')),
        ];
        $values = array_filter($dimensions, fn ($value) => trim((string) $value) !== '');
        if (!$values) {
            return null;
        }

        $answered = count($values);
        // V8 gives the structured SAR dimension partial coverage when Situation,
        // Action, or Result is missing. A Situation + Result answer is not the same
        // evidence completeness as a full Situation-Action-Result response.
        $coverage = 0;
        if (trim((string) ($dimensions['Role skills / tools'] ?? '')) !== '') $coverage += 25;
        if (trim((string) ($dimensions['Similar work example'] ?? '')) !== '') $coverage += 25;
        if (trim((string) ($answers['role_problem_solving'] ?? '')) !== '') $coverage += 7;
        if (trim((string) ($answers['role_problem_action'] ?? '')) !== '') $coverage += 10;
        if (trim((string) ($answers['role_problem_result'] ?? '')) !== '') $coverage += 8;
        if (trim((string) ($dimensions['Strongest requirement proof'] ?? '')) !== '') $coverage += 25;
        $coverage = min(100, $coverage);
        $specificityScores = [];
        $allAction = 0;
        $allResult = 0;
        $toolEvidence = [];

        foreach ($values as $label => $value) {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($value)));
            $words = str_word_count($plain);
            $actions = $this->actionSignalCount($plain);
            $results = $this->resultSignalCount($plain);
            // Bare product/version numbers (for example Windows 11 or Microsoft 365)
            // are not quantitative evidence. Count only numbers tied to a meaningful
            // workload, outcome, duration, or quality unit.
            $numbers = $this->measurableEvidenceCount($plain) > 0 ? 1 : 0;
            $tools = $this->toolNamesInText($plain);

            $base = $words >= 40 ? 84 : ($words >= 24 ? 74 : ($words >= 12 ? 60 : ($words >= 6 ? 48 : 34)));
            if ($label === 'Role skills / tools' && count($tools) > 0) {
                $base += 5;
            }
            if ($label === 'Problem-solving SAR') {
                $hasSituation = trim((string) ($answers['role_problem_solving'] ?? '')) !== '';
                $hasSeparateAction = trim((string) ($answers['role_problem_action'] ?? '')) !== '';
                $hasSeparateResult = trim((string) ($answers['role_problem_result'] ?? '')) !== '';
                $base += $hasSituation ? 2 : -8;
                $base += $hasSeparateAction ? 7 : -18;
                $base += $hasSeparateResult ? 7 : -16;
            }

            $specificityScores[] = min(100, $base + min(12, ($actions + $results) * 3) + ($numbers ? 5 : 0));
            $allAction += $actions;
            $allResult += $results;
            $toolEvidence = array_merge($toolEvidence, $tools);
        }

        $specificity = $specificityScores ? (int) round(array_sum($specificityScores) / count($specificityScores)) : 0;
        $normalized = collect($values)
            ->map(fn ($value) => Str::lower(trim(preg_replace('/\s+/', ' ', strip_tags($value)))))
            ->filter();
        $distinctAnswers = $normalized->unique()->count();
        $duplicateAnswers = max(0, $normalized->count() - $distinctAnswers);

        $score = (int) round(($coverage * .45) + ($specificity * .55));
        if ($duplicateAnswers > 0) {
            $score = max(0, $score - min(15, $duplicateAnswers * 5));
        }
        $score = min(100, $score);

        return [
            'score' => $score,
            'coverage' => $coverage,
            'detail' => $answered . '/' . count($dimensions) . ' role-evidence dimensions answered · specificity ' . $specificity
                . '% · ' . ($allAction + $allResult) . ' concrete action/result signal(s). This is Role Evidence Quality only; it does not add Role Fit points.',
            'evidence_items' => array_keys($values),
            'comparison' => [
                'metric' => $score,
                'primary' => $this->reliabilityLabel($score) . ' role-answer evidence quality',
                'secondary' => $coverage . '% evidence-dimension coverage · ' . $specificity . '% specificity',
                'facts' => [
                    ['label' => 'Evidence dimensions answered', 'value' => $answered . '/' . count($dimensions)],
                    ['label' => 'Distinct evidence dimensions', 'value' => $distinctAnswers . '/' . count($dimensions)],
                    ['label' => 'Structured SAR completeness', 'value' => collect([
                        trim((string) ($answers['role_problem_solving'] ?? '')) !== '' ? 'Situation' : null,
                        trim((string) ($answers['role_problem_action'] ?? '')) !== '' ? 'Action' : null,
                        trim((string) ($answers['role_problem_result'] ?? '')) !== '' ? 'Result' : null,
                    ])->filter()->implode(' + ') ?: 'Not provided'],
                    ['label' => 'Concrete action/result signals', 'value' => (string) ($allAction + $allResult)],
                    ['label' => 'Named tools mentioned', 'value' => $toolEvidence ? implode(', ', array_slice(array_values(array_unique($toolEvidence)), 0, 8)) : 'None detected'],
                ],
            ],
        ];
    }

    private function educationAssessment(array $answers, JobVacancy $vacancy): ?array
    {
        $elementary = trim((string) ($answers['elementary_school'] ?? ''));
        $highschool = trim((string) ($answers['highschool_school'] ?? ''));
        $vocational = trim((string) ($answers['vocational_school'] ?? ''));
        $college = trim((string) ($answers['college_school'] ?? ''));
        $degree = trim((string) ($answers['college_course'] ?? ''));
        $collegeDates = trim((string) ($answers['college_dates'] ?? ''));

        if ($elementary === '' && $highschool === '' && $vocational === '' && $college === '' && $degree === '') {
            return null;
        }

        $requirements = $this->qualificationRequirements($vacancy)
            ->filter(fn ($requirement) => $this->inferQualificationType(
                (string) ($requirement->qualification_type ?? 'auto'),
                (string) $requirement->qualification_text
            ) === 'education')
            ->values();

        $values = [$elementary, $highschool, $vocational, $college, $degree];
        $coverage = (int) round((count(array_filter($values, fn ($value) => $value !== '')) / count($values)) * 100);
        $requirementNote = $requirements->isNotEmpty()
            ? $requirements->count() . ' education vacancy requirement(s) are scored once under Vacancy Qualification Match.'
            : 'No explicit education requirement in this vacancy; shown for HR context only.';

        return [
            'score' => null,
            'coverage' => $coverage,
            'detail' => 'Context only — ' . $requirementNote . ' The simplified form collects school history and Degree/Course only. Degree completion is not asked as a separate question, so applicant-declared degree wording remains subject to HR/document verification.',
            'evidence_items' => array_values(array_filter([$degree, $college, $vocational, $highschool, $elementary])),
            'comparison' => [
                'metric' => null,
                'primary' => $degree ?: ($college ?: 'College/degree not provided'),
                'secondary' => $requirements->isNotEmpty() ? 'Requirement handled in Vacancy Qualification Match — not double-counted' : 'Informational only',
                'facts' => [
                    ['label' => 'Elementary school', 'value' => $elementary ?: 'Not provided'],
                    ['label' => 'High school', 'value' => $highschool ?: 'Not provided'],
                    ['label' => 'Vocational school / course', 'value' => $vocational ?: 'Not provided'],
                    ['label' => 'College / university', 'value' => $college ?: 'Not provided'],
                    ['label' => 'Degree / course', 'value' => $degree ?: 'Not provided'],
                    ['label' => 'College inclusive dates', 'value' => $collegeDates ?: 'Not provided'],
                ],
            ],
        ];
    }

    private function availabilityAssessment(array $answers, JobVacancy $vacancy): ?array
    {
        $availability = trim((string) ($answers['availability'] ?? ''));
        $status = trim((string) ($answers['current_employment_status'] ?? ''));
        $notice = trim((string) ($answers['notice_period'] ?? ''));
        if ($availability === '' && $status === '' && $notice === '') {
            return null;
        }

        $noticeApplicable = in_array($status, ['Employed','Self-Employed','Freelance / Project-Based'], true);
        $dimensions = [
            $availability !== '',
            $status !== '',
            !$noticeApplicable || $notice !== '',
        ];
        $coverage = (int) round((count(array_filter($dimensions)) / count($dimensions)) * 100);
        $hasRequirement = $this->qualificationRequirements($vacancy)
            ->contains(fn ($requirement) => $this->inferQualificationType(
                (string) ($requirement->qualification_type ?? 'auto'),
                (string) $requirement->qualification_text
            ) === 'availability');

        return [
            'score' => null,
            'coverage' => $coverage,
            'detail' => 'Basic readiness context only — Start: ' . ($availability ?: 'not provided')
                . ' · Current status: ' . ($status ?: 'not provided')
                . ' · Notice: ' . ($notice ?: ($noticeApplicable ? 'not provided' : 'not applicable')) . '. '
                . ($hasRequirement ? 'Any explicit vacancy readiness requirement is scored once under Vacancy Qualification Match.' : 'No vacancy readiness requirement is being scored.'),
            'evidence_items' => array_values(array_filter([$availability,$status,$notice])),
            'comparison' => [
                'metric' => null,
                'primary' => $availability ?: 'Availability not provided',
                'secondary' => 'Current status: ' . ($status ?: 'not provided') . ' · Notice: ' . ($notice ?: ($noticeApplicable ? 'not provided' : 'not applicable')),
                'facts' => [
                    ['label' => 'Availability to start', 'value' => $availability ?: 'Not provided'],
                    ['label' => 'Current employment status', 'value' => $status ?: 'Not provided'],
                    ['label' => 'Notice period', 'value' => $notice ?: ($noticeApplicable ? 'Not provided' : 'Not applicable')],
                ],
            ],
        ];
    }

    private function requiredExperienceMonths(JobVacancy $vacancy): ?int
    {
        foreach ($this->qualificationRequirements($vacancy) as $requirement) {
            $text = (string) $requirement->qualification_text;
            if ($this->inferQualificationType((string) ($requirement->qualification_type ?? 'auto'), $text) !== 'experience') {
                continue;
            }

            if ($this->effectiveRequirementLevel((string) $requirement->requirement_level, $text) !== 'required') {
                continue;
            }

            $minimum = $requirement->minimum_value;
            $unit = $requirement->minimum_unit;
            if ($minimum === null && preg_match('/\b(?:at\s+least\s+|minimum(?:\s+of)?\s+)?(\d+(?:\.\d+)?)\s*(?:(?:-|–|to)\s*\d+(?:\.\d+)?\s*)?\+?\s*(years?|yrs?|months?|mos?)\b/iu', $text, $match)) {
                $minimum = (float) $match[1];
                $unit = str_starts_with(strtolower($match[2]), 'mo') ? 'months' : 'years';
            }
            if ($minimum !== null) {
                return $unit === 'months' ? (int) round($minimum) : (int) round($minimum * 12);
            }
        }
        return null;
    }

    private function weightedCoverage(array $scores): float
    {
        $weighted = 0.0;
        $total = 0;
        foreach ($scores as $row) {
            if (($row['key'] ?? '') === 'vacancy_qualification_match' || !($row['contributes_to_fit'] ?? false)) {
                continue;
            }
            $weight = (int) ($row['weight'] ?? 0);
            if ($weight <= 0) {
                continue;
            }
            $weighted += ((int) ($row['coverage'] ?? 0)) * $weight;
            $total += $weight;
        }
        return $total > 0 ? round($weighted / $total, 1) : 0.0;
    }

    private function buildInterviewFocus(array $scores, ?array $qualificationAssessment): array
    {
        $focus = collect();

        if ($qualificationAssessment) {
            foreach ($qualificationAssessment['qualification_details'] ?? [] as $detail) {
                if ($detail['level'] === 'required' && in_array($detail['status'], ['not_evidenced','needs_verification','not_matched'], true)) {
                    $prefix = $detail['status'] === 'not_matched' ? 'Challenge/verify' : 'Verify';
                    $focus->push($prefix . ' required qualification: ' . $detail['qualification'] . ' — ' . $detail['evidence']);
                    if ($focus->count() >= 2) {
                        break;
                    }
                }
            }
        }

        $weak = collect($scores)
            ->filter(fn ($row) => ($row['key'] ?? '') !== 'vacancy_qualification_match' && ($row['contributes_to_fit'] ?? false))
            ->sortBy(fn ($row) => (($row['score'] ?? 50) * .75) + (($row['coverage'] ?? 0) * .25));

        foreach ($weak as $row) {
            if ($focus->count() >= 3) {
                break;
            }
            $focus->push('Clarify ' . $row['name'] . ': ' . $this->shortDetail($row));
        }

        return $focus->filter()->unique()->take(3)->values()->all();
    }

    private function shortDetail(array $row): string
    {
        $comparison = $row['comparison'] ?? null;
        if (is_array($comparison) && !empty($comparison['primary'])) {
            return $comparison['primary'] . (!empty($comparison['secondary']) ? ' · ' . $comparison['secondary'] : '');
        }
        return Str::limit((string) ($row['detail'] ?? 'No detail available.'), 190);
    }

    private function presentValues(array $answers, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $value = trim((string) ($answers[$key] ?? ''));
            if ($value !== '') {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    private function reliabilityLabel(int $score): string
    {
        return $score >= 85 ? 'High'
            : ($score >= 70 ? 'Good'
                : ($score >= 55 ? 'Moderate' : 'Low'));
    }

    private function educationAdjacentMatch(array $required, array $applicant): bool
    {
        if (!$required || !$applicant) {
            return false;
        }

        $families = [
            ['accounting','finance','business','economics','commerce'],
            ['information_technology','engineering_computer'],
            ['human_resources','psychology','business'],
            ['marketing','communications','business'],
        ];
        foreach ($families as $family) {
            if (array_intersect($required, $family) && array_intersect($applicant, $family)) {
                return true;
            }
        }
        return false;
    }

    /** @return array<string,array{category:string,aliases:array<int,string>}> */
    private function toolCatalog(): array
    {
        return [
            'NetSuite' => ['category' => 'accounting_erp', 'aliases' => ['netsuite','oracle netsuite']],
            'QuickBooks' => ['category' => 'accounting_erp', 'aliases' => ['quickbooks','quick books']],
            'Xero' => ['category' => 'accounting_erp', 'aliases' => ['xero']],
            'SAP' => ['category' => 'accounting_erp', 'aliases' => ['sap']],
            'Oracle ERP' => ['category' => 'accounting_erp', 'aliases' => ['oracle erp','oracle financials']],
            'Sage' => ['category' => 'accounting_erp', 'aliases' => ['sage accounting','sage 50','sage']],
            'Odoo' => ['category' => 'accounting_erp', 'aliases' => ['odoo']],
            'MYOB' => ['category' => 'accounting_erp', 'aliases' => ['myob']],
            'FreshBooks' => ['category' => 'accounting_erp', 'aliases' => ['freshbooks']],
            'Zoho Books' => ['category' => 'accounting_erp', 'aliases' => ['zoho books']],
            'Microsoft Excel' => ['category' => 'spreadsheet', 'aliases' => ['microsoft excel','ms excel','excel']],
            'Google Sheets' => ['category' => 'spreadsheet', 'aliases' => ['google sheets']],
            'Power BI' => ['category' => 'bi', 'aliases' => ['power bi','powerbi']],
            'Tableau' => ['category' => 'bi', 'aliases' => ['tableau']],
            'Microsoft Word' => ['category' => 'word_processing', 'aliases' => ['microsoft word','ms word']],
            'Microsoft PowerPoint' => ['category' => 'presentation', 'aliases' => ['microsoft powerpoint','ms powerpoint','powerpoint']],
            'Google Workspace' => ['category' => 'office_suite', 'aliases' => ['google workspace','g suite','gsuite']],
            'Salesforce' => ['category' => 'crm', 'aliases' => ['salesforce']],
            'HubSpot' => ['category' => 'crm', 'aliases' => ['hubspot']],
            'Zoho CRM' => ['category' => 'crm', 'aliases' => ['zoho crm']],
            'SQL' => ['category' => 'query_language', 'aliases' => ['sql']],
            'MySQL' => ['category' => 'relational_database', 'aliases' => ['mysql']],
            'PostgreSQL' => ['category' => 'relational_database', 'aliases' => ['postgresql','postgres']],
            'PHP' => ['category' => 'programming_language', 'aliases' => ['php']],
            'Laravel' => ['category' => 'backend_framework', 'aliases' => ['laravel']],
            'JavaScript' => ['category' => 'programming_language', 'aliases' => ['javascript']],
            'TypeScript' => ['category' => 'programming_language', 'aliases' => ['typescript']],
            'React' => ['category' => 'frontend_framework', 'aliases' => ['react.js','reactjs','react']],
            'Vue' => ['category' => 'frontend_framework', 'aliases' => ['vue.js','vuejs','vue']],
            'Node.js' => ['category' => 'runtime', 'aliases' => ['node.js','nodejs']],
            'Python' => ['category' => 'programming_language', 'aliases' => ['python']],
            'Java' => ['category' => 'programming_language', 'aliases' => ['java']],
            'AutoCAD' => ['category' => 'engineering_cad', 'aliases' => ['autocad']],
            'SolidWorks' => ['category' => 'engineering_cad', 'aliases' => ['solidworks']],
            'Adobe Photoshop' => ['category' => 'creative', 'aliases' => ['adobe photoshop','photoshop']],
            'Adobe Illustrator' => ['category' => 'creative', 'aliases' => ['adobe illustrator','illustrator']],
            'Adobe Premiere Pro' => ['category' => 'creative', 'aliases' => ['adobe premiere pro','premiere pro','premiere']],
            'After Effects' => ['category' => 'creative', 'aliases' => ['after effects','adobe after effects']],
            'Canva' => ['category' => 'creative', 'aliases' => ['canva']],
            'Figma' => ['category' => 'creative', 'aliases' => ['figma']],
            'WordPress' => ['category' => 'ecommerce_web', 'aliases' => ['wordpress']],
            'WooCommerce' => ['category' => 'ecommerce_web', 'aliases' => ['woocommerce']],
            'Shopify' => ['category' => 'ecommerce_web', 'aliases' => ['shopify']],
            'Jira' => ['category' => 'project_management', 'aliases' => ['jira']],
            'Trello' => ['category' => 'project_management', 'aliases' => ['trello']],
            'Asana' => ['category' => 'project_management', 'aliases' => ['asana']],
            'Slack' => ['category' => 'collaboration', 'aliases' => ['slack']],
            // Common IT support / system-administration technologies.
            'Windows' => ['category' => 'operating_system', 'aliases' => ['windows 11','windows 10','microsoft windows','windows']],
            'Windows Server' => ['category' => 'server_operating_system', 'aliases' => ['windows server 2025','windows server 2022','windows server 2019','windows server 2016','windows server']],
            'Active Directory' => ['category' => 'directory_service', 'aliases' => ['active directory','microsoft active directory','ad ds','adds']],
            'Microsoft 365' => ['category' => 'cloud_productivity', 'aliases' => ['microsoft 365','office 365','m365','microsoft 365 admin center']],
            'DNS' => ['category' => 'network_service', 'aliases' => ['domain name system','dns']],
            'DHCP' => ['category' => 'network_service', 'aliases' => ['dynamic host configuration protocol','dhcp']],
            'Remote Desktop' => ['category' => 'remote_support', 'aliases' => ['remote desktop','rdp']],
            'AnyDesk' => ['category' => 'remote_support', 'aliases' => ['anydesk']],
            'TeamViewer' => ['category' => 'remote_support', 'aliases' => ['teamviewer','team viewer']],
        ];
    }

    /** @return array<int,string> */
    private function toolNamesInText(string $text): array
    {
        $lower = Str::lower($text);
        $found = [];
        foreach ($this->toolCatalog() as $name => $meta) {
            foreach ($meta['aliases'] as $alias) {
                $pattern = '/(?<![a-z0-9])' . preg_quote(Str::lower($alias), '/') . '(?![a-z0-9])/i';
                if (preg_match($pattern, $lower)) {
                    $found[] = $name;
                    break;
                }
            }
        }
        return array_values(array_unique($found));
    }

    private function toolCategory(string $name): ?string
    {
        return $this->toolCatalog()[$name]['category'] ?? null;
    }

    /** @param array<int,string> $vacancyTools @param array<int,string> $evidenceTools */
    private function relevantToolMatches(array $vacancyTools, array $evidenceTools, string $requirementContext): array
    {
        if (!$vacancyTools || !$evidenceTools) {
            return [];
        }

        $matched = array_values(array_intersect($vacancyTools, $evidenceTools));

        // MySQL/PostgreSQL usage necessarily involves SQL, so they can establish a
        // generic SQL requirement. The reverse is not assumed: saying only "SQL"
        // does not prove experience with a specifically required database product.
        if (in_array('SQL', $vacancyTools, true)) {
            foreach (['MySQL', 'PostgreSQL'] as $sqlDatabase) {
                if (in_array($sqlDatabase, $evidenceTools, true)) {
                    $matched[] = $sqlDatabase;
                }
            }
        }

        $allowsSimilar = preg_match('/\b(or\s+similar|similar\s+(?:system|software|tool|platform)|equivalent)\b/i', $requirementContext);
        if ($allowsSimilar) {
            // Only categories with reasonably interchangeable products are allowed
            // to satisfy "or similar". Broad technology categories are intentionally
            // excluded so PHP does not satisfy JavaScript, Excel does not satisfy
            // Power BI, and a generic design tool does not satisfy a video editor.
            $interchangeable = [
                'accounting_erp','spreadsheet','bi','crm','relational_database',
                'frontend_framework','engineering_cad','project_management',
            ];
            $requiredCategories = array_values(array_unique(array_filter(array_map(fn ($tool) => $this->toolCategory($tool), $vacancyTools))));
            $requiredCategories = array_values(array_intersect($requiredCategories, $interchangeable));
            foreach ($evidenceTools as $tool) {
                if (in_array($this->toolCategory($tool), $requiredCategories, true)) {
                    $matched[] = $tool;
                }
            }
        }
        return array_values(array_unique($matched));
    }

    /** @return array<int,string> */
    private function preferredToolNamesFromRequirement(string $text): array
    {
        $clauses = preg_split('/(?<=[.!?;])\s+|\s*;\s*/', trim($text)) ?: [$text];
        $preferred = [];

        foreach ($clauses as $clause) {
            if (!preg_match('/\b(preferred|preferably|advantage|an\s+advantage|desirable|nice\s+to\s+have|bonus)\b/i', $clause, $marker, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $markerText = (string) $marker[0][0];
            $offset = (int) $marker[0][1];
            $before = trim(substr($clause, 0, $offset));
            $after = trim(substr($clause, $offset + strlen($markerText)));

            // "preferably NetSuite" / "preferred NetSuite" points forward.
            if (preg_match('/\b(preferred|preferably|desirable|nice\s+to\s+have|bonus)\b/i', $markerText)) {
                $afterTools = $this->toolNamesInText($after);
                if ($afterTools) {
                    $preferred = array_merge($preferred, $afterTools);
                    continue;
                }
            }

            // "NetSuite is an advantage" / "NetSuite preferred" points backward.
            // Use only the closest named tool so a required tool earlier in the same
            // sentence is not accidentally converted into a preference.
            $beforeTools = $this->toolNamesInText($before);
            if ($beforeTools) {
                $preferred[] = end($beforeTools);
                continue;
            }

            $afterTools = $this->toolNamesInText($after);
            if ($afterTools) {
                $preferred[] = $afterTools[0];
            }
        }

        return array_values(array_unique(array_filter($preferred)));
    }

    /**
     * Remove preference-only clauses/tails before deciding what a hard tool
     * requirement actually asks for. This prevents "NetSuite is an advantage"
     * from turning NetSuite into the required tool when the base requirement is
     * QuickBooks (or a generic accounting system).
     */
    private function baseToolRequirementText(string $text): string
    {
        $clauses = preg_split('/(?<=[.!?;])\s+|\s*;\s*/', trim($text)) ?: [$text];
        $base = [];
        $preferredPattern = '/\b(preferred|preferably|advantage|an\s+advantage|desirable|nice\s+to\s+have|bonus)\b/i';

        foreach ($clauses as $clause) {
            if (!preg_match($preferredPattern, $clause, $marker, PREG_OFFSET_CAPTURE)) {
                $base[] = $clause;
                continue;
            }

            $offset = (int) $marker[0][1];
            $prefix = trim(rtrim(substr($clause, 0, $offset), " ,:-"));
            $hasHardLanguage = (bool) preg_match('/\b(must|required|mandatory|at\s+least|minimum|need(?:s|ed)?|experience\s+(?:using|with)|proficien|knowledge\s+of|familiar\s+with)\b/i', $prefix);

            if ($hasHardLanguage) {
                // For "Must use QuickBooks, preferably NetSuite", keep the hard
                // portion before the preference marker.
                $base[] = $prefix;
                continue;
            }

            // For "NetSuite is an advantage", the whole clause is preference-only.
            // If there is non-tool requirement wording after the marker, it will be
            // assessed elsewhere rather than being treated as a required tool.
        }

        return trim(implode(' ', array_filter($base)));
    }

    /** @return array<int,string> */
    private function genericToolCategoriesFromRequirement(string $text): array
    {
        $lower = Str::lower($text);
        $categories = [];

        if (preg_match('/\b(accounting|bookkeeping|finance|financial)\b.*\b(system|software|erp|platform)\b|\b(erp)\b.*\b(accounting|finance|financial)\b/i', $lower)) {
            $categories[] = 'accounting_erp';
        }
        if (preg_match('/\bspreadsheet(?:\s+software|\s+tool)?\b/i', $lower)) {
            $categories[] = 'spreadsheet';
        }
        if (preg_match('/\b(business\s+intelligence|bi\s+tool|analytics\s+dashboard\s+tool)\b/i', $lower)) {
            $categories[] = 'bi';
        }
        if (preg_match('/\b(crm|customer\s+relationship\s+management)\b/i', $lower)) {
            $categories[] = 'crm';
        }
        if (preg_match('/\b(project\s+management|task\s+management)\b.*\b(tool|software|platform)\b/i', $lower)) {
            $categories[] = 'project_management';
        }
        if (preg_match('/\b(cad|computer[- ]aided\s+design)\b.*\b(tool|software|platform)?\b/i', $lower)) {
            $categories[] = 'engineering_cad';
        }

        return array_values(array_unique($categories));
    }

    /**
     * Break multi-part skill requirements into canonical evidence components.
     * This prevents one keyword (for example "Windows") from satisfying an
     * entire requirement that also asks for hardware/software support and networking.
     *
     * @return array<int,array{key:string,label:string}>
     */
    private function compoundSkillComponents(string $text): array
    {
        $lower = Str::lower($text);
        $components = [];
        $add = static function (array &$rows, string $key, string $label): void {
            if (!collect($rows)->contains(fn ($row) => $row['key'] === $key)) {
                $rows[] = ['key' => $key, 'label' => $label];
            }
        };

        $windowsSpecific = Str::contains($lower, 'windows')
            && (bool) preg_match('/\b(troubleshoot|troubleshooting|support|administration|administer|configure|configuration)\b/i', $text);
        if ($windowsSpecific) {
            $add($components, 'windows_support', 'Windows troubleshooting / support');
        }

        if (preg_match('/\bhardware(?:\s*\/\s*software|\s+and\s+software)?\s+support\b|\bhardware\b.*\bsoftware\b.*\bsupport\b/i', $text)) {
            $add($components, 'hardware_software_support', 'Hardware & software support');
        } elseif (preg_match('/\bhardware\s+(?:support|troubleshooting|diagnostics?|repair)\b/i', $text)) {
            $add($components, 'hardware_support', 'Hardware support');
        }

        if (preg_match('/\b(?:basic\s+)?network(?:ing)?\b|\btcp\/?ip\b|\blan\b|\bwan\b/i', $text)) {
            $add($components, 'networking', 'Basic networking');
        }

        if (preg_match('/\b(documentation|documenting|document)\b/i', $text)) {
            $add($components, 'documentation', 'Documentation');
        }

        if (preg_match('/\b(user[- ]support|end[- ]user\s+support|help\s*desk|technical\s+support)\b/i', $text)) {
            $add($components, 'user_support', 'User support');
        }

        if (!$windowsSpecific && preg_match('/\b(troubleshoot|troubleshooting|problem[- ]solving|problem\s+solving)\b/i', $text)) {
            $add($components, 'troubleshooting', 'Troubleshooting / problem solving');
        }

        if (preg_match('/\bsystem\s+administration\b|\bsystems?\s+administrator\b/i', $text)) {
            $add($components, 'system_administration', 'System administration');
        }

        return $components;
    }

    /**
     * @param array<int,array{key:string,label:string}> $components
     */
    private function evaluateCompoundSkillQualification(string $requirement, array $components, array $answers): array
    {
        $source = $this->positiveQualificationEvidenceText($answers);
        $trainingGap = trim((string) ($answers['role_training_gap'] ?? ''));
        $componentEvidence = [];
        $matched = 0;
        $pending = 0;
        $explicitMissing = 0;

        foreach ($components as $component) {
            $evaluation = $this->evaluateSkillComponent($component['key'], $component['label'], $source, $trainingGap);
            $componentEvidence[] = $evaluation;
            if ($evaluation['status'] === 'matched') {
                $matched++;
            } elseif ($evaluation['status'] === 'not_matched') {
                $explicitMissing++;
            } else {
                $pending++;
            }
        }

        $usesOr = (bool) preg_match('/\bor\b/i', $requirement)
            && !(bool) preg_match('/\band\b/i', $requirement);
        $total = count($components);

        if ($usesOr) {
            $status = $matched > 0 ? 'matched'
                : ($explicitMissing === $total ? 'not_matched' : ($pending > 0 ? 'needs_verification' : 'not_evidenced'));
        } else {
            if ($explicitMissing > 0) {
                $status = 'not_matched';
            } elseif ($matched === $total) {
                $status = 'matched';
            } elseif ($matched > 0 || $pending > 0) {
                $status = 'needs_verification';
            } else {
                $status = 'not_evidenced';
            }
        }

        $matchedLabels = array_values(array_map(
            fn ($row) => $row['label'],
            array_filter($componentEvidence, fn ($row) => $row['status'] === 'matched')
        ));
        $pendingLabels = array_values(array_map(
            fn ($row) => $row['label'],
            array_filter($componentEvidence, fn ($row) => in_array($row['status'], ['needs_verification','not_evidenced'], true))
        ));

        $evidence = $matched . '/' . $total . ' requirement component(s) evidenced.';
        if ($matchedLabels) {
            $evidence .= ' Evidenced: ' . implode(', ', $matchedLabels) . '.';
        }
        if ($pendingLabels) {
            $evidence .= ' Still to verify: ' . implode(', ', $pendingLabels) . '.';
        }

        return $this->qualificationResult('skill', $status, $evidence, $matched, $matched . '/' . $total . ' components evidenced', $total, [
            'evidence_source' => 'Role-specific skills / tools / comparable-work / problem-solving evidence',
            'component_evidence' => $componentEvidence,
        ]);
    }

    /** @return array{key:string,label:string,status:string,evidence:string} */
    private function evaluateSkillComponent(string $key, string $label, string $source, string $trainingGap): array
    {
        $sourceLower = Str::lower($source);
        $matched = match ($key) {
            'windows_support' => Str::contains($sourceLower, 'windows')
                && (bool) preg_match('/\b(troubleshoot|troubleshooting|support|configured|configuration|installed|installation|profile|credential|driver)\b/i', $source),
            'hardware_software_support' => (bool) preg_match('/\b(hardware|desktop|laptop|ram|storage|drive|printer|device)\b/i', $source)
                && (bool) preg_match('/\b(software|application|applications|windows|driver|outlook|microsoft\s*365|office\s*365|install|installed|profile)\b/i', $source),
            'hardware_support' => (bool) preg_match('/\b(hardware|desktop|laptop|ram|storage|drive|printer|device|motherboard|memory)\b/i', $source)
                && (bool) preg_match('/\b(repair|repaired|replace|replaced|replacement|diagnos|troubleshoot|tested|testing|reseated|install|installed)\b/i', $source),
            'networking' => (bool) preg_match('/\b(network|networking|tcp\/?ip|lan|wan|router|switch|gateway|dns|dhcp|ip\s+address|connectivity|ping|ipconfig)\b/i', $source),
            'documentation' => (bool) preg_match('/\b(document|documented|documentation|ticket|ticketing|recorded|record\s+keeping|knowledge\s+base|log|logged)\b/i', $source),
            'user_support' => (bool) preg_match('/\b(user|users|end[- ]user|help\s*desk|technical\s+support|it\s+support|client|customer)\b/i', $source)
                && (bool) preg_match('/\b(support|supported|assist|assisted|helped|resolved|troubleshoot|handled|communicat|continue\s+work)\b/i', $source),
            'troubleshooting' => (bool) preg_match('/\b(troubleshoot|troubleshooting|diagnos|investigat|analyz|root\s+cause|resolved|fixed|identified|tested|checked|verified)\b/i', $source),
            'system_administration' => (bool) preg_match('/\b(system\s+administration|system\s+administrator|active\s+directory|windows\s+server|admin\s+center|user\s+administration|account\s+administration|configured|administered)\b/i', $source),
            default => false,
        };

        if ($matched) {
            return ['key' => $key, 'label' => $label, 'status' => 'matched', 'evidence' => 'Concrete role-focused evidence detected.'];
        }

        $negativePatterns = match ($key) {
            'windows_support' => ['windows'],
            'hardware_software_support','hardware_support' => ['hardware','software'],
            'networking' => ['networking','network','dns','dhcp','tcp/ip'],
            'documentation' => ['documentation','documenting'],
            'user_support' => ['user support','help desk','technical support'],
            'troubleshooting' => ['troubleshooting','problem solving'],
            'system_administration' => ['system administration','active directory','windows server'],
            default => [],
        };
        $gapMatches = collect($negativePatterns)->contains(fn ($term) => Str::contains(Str::lower($trainingGap), Str::lower($term)));
        $explicitNo = collect($negativePatterns)->contains(function ($term) use ($source) {
            $quoted = preg_quote($term, '/');
            return (bool) preg_match('/\b(?:no|without|do\s+not\s+have|don\'t\s+have|limited|little)\b[^.!?]{0,45}\b' . $quoted . '\b/i', $source);
        });

        if ($explicitNo) {
            return ['key' => $key, 'label' => $label, 'status' => 'not_matched', 'evidence' => 'Applicant explicitly described insufficient experience in this component.'];
        }
        if ($gapMatches) {
            return ['key' => $key, 'label' => $label, 'status' => 'needs_verification', 'evidence' => 'Applicant identified this component as a training/development gap.'];
        }
        return ['key' => $key, 'label' => $label, 'status' => 'not_evidenced', 'evidence' => 'No sufficiently specific evidence found for this component.'];
    }

    private function isToolRequirement(string $text): bool
    {
        return $this->toolNamesInText($text) !== []
            || (bool) preg_match('/\b(software|system|systems|tool|tools|platform|erp|crm|spreadsheet)\b/i', $text);
    }

    private function evaluateToolQualification(string $text, array $answers, JobVacancy $vacancy): array
    {
        $source = $this->toolUsageEvidenceText($answers);
        $directSource = $this->directToolUsageEvidenceText($answers);
        $trainingGap = trim((string) ($answers['role_training_gap'] ?? ''));
        $overallLevel = $this->inferRequirementLevel($text);
        // Only strip a preferred sub-clause when it is embedded inside a hard
        // requirement. If the whole qualification is itself preferred (for example
        // "NetSuite experience is preferred"), that preferred item is the thing we
        // are assessing and must remain in the base text.
        $baseRequirement = $overallLevel === 'required'
            ? $this->baseToolRequirementText($text)
            : $text;
        $requiredTools = $this->toolNamesInText($baseRequirement);
        $evidenceTools = $this->toolNamesInText($source);
        $directEvidenceTools = $this->toolNamesInText($directSource);
        $matches = $this->relevantToolMatches($requiredTools, $directEvidenceTools, $baseRequirement);
        $weakMatches = $this->relevantToolMatches($requiredTools, $evidenceTools, $baseRequirement);
        $exact = array_values(array_intersect($requiredTools, $directEvidenceTools));
        $allowsSimilar = (bool) preg_match('/\b(or\s+similar|similar\s+(?:system|software|tool|platform)|equivalent)\b/i', $baseRequirement);
        $genericCategories = $this->genericToolCategoriesFromRequirement($baseRequirement);

        // A domain-specific generic requirement such as "accounting software"
        // may be satisfied by a named tool in that domain. A bare word like
        // "system" has no such category and therefore remains unverified.
        if (!$matches && !$requiredTools && $genericCategories && $directEvidenceTools) {
            $matches = array_values(array_filter($directEvidenceTools, fn ($tool) => in_array($this->toolCategory($tool), $genericCategories, true)));
        }
        if (!$weakMatches && !$requiredTools && $genericCategories && $evidenceTools) {
            $weakMatches = array_values(array_filter($evidenceTools, fn ($tool) => in_array($this->toolCategory($tool), $genericCategories, true)));
        }

        $preferredTools = $overallLevel === 'required'
            ? $this->preferredToolNamesFromRequirement($text)
            : [];
        $preferredBonus = $preferredTools ? implode(', ', $preferredTools) . ' preferred experience' : null;
        $preferredBonusMatched = $preferredTools
            ? count(array_intersect($preferredTools, $directEvidenceTools)) > 0
            : null;

        $requiresAllNamedTools = count($requiredTools) > 1
            && preg_match('/\band\b/i', $baseRequirement)
            && !preg_match('/\bor\b/i', $baseRequirement);
        $missingNamedTools = $requiresAllNamedTools
            ? array_values(array_diff($requiredTools, array_values(array_intersect($requiredTools, $directEvidenceTools))))
            : [];

        if ($this->explicitLackOfRequirement($text, $source)) {
            return $this->qualificationResult('skill', 'not_matched', 'The applicant explicitly indicated no/insufficient experience with the required system/tool.', 0, 'Explicit lack of tool experience', null, [
                'preferred_bonus' => $preferredBonus,
                'preferred_bonus_matched' => $preferredBonusMatched,
                'evidence_source' => 'Role-specific exact tools / comparable-work evidence',
            ]);
        }

        if ($requiresAllNamedTools && $missingNamedTools) {
            return $this->qualificationResult('skill', 'not_evidenced',
                'Only part of the combined named-tool requirement is evidenced. Missing/unevidenced: ' . implode(', ', $missingNamedTools) . '.',
                count($matches) ?: null,
                $matches ? implode(', ', $matches) . ' evidenced' : 'No complete named-tool match',
                null,
                [
                    'preferred_bonus' => $preferredBonus,
                    'preferred_bonus_matched' => $preferredBonusMatched,
                    'evidence_source' => $source !== '' ? 'Role-specific skills / tools evidence' : null,
                ]
            );
        }

        if ($matches) {
            $kind = $exact ? 'directly named' : ($requiredTools ? 'accepted similar-category' : 'domain-matched named tool');
            return $this->qualificationResult('skill', 'matched',
                'Relevant system/tool evidence: ' . implode(', ', $matches) . ' (' . $kind . ' match).',
                count($matches), implode(', ', $matches), null, [
                    'preferred_bonus' => $preferredBonus,
                    'preferred_bonus_matched' => $preferredBonusMatched,
                    'evidence_source' => 'Role-specific exact tools / comparable-work evidence',
                ]);
        }

        if ($weakMatches) {
            return $this->qualificationResult('skill', 'needs_verification',
                'The required tool/system is named in general skills evidence (' . implode(', ', $weakMatches) . '), but the assessment found no direct usage evidence in the exact tools/systems answer, comparable-work example, or problem-solving evidence.',
                count($weakMatches), implode(', ', $weakMatches) . ' claimed — usage not established', null, [
                    'preferred_bonus' => $preferredBonus,
                    'preferred_bonus_matched' => $preferredBonusMatched,
                    'evidence_source' => 'General skills claim only',
                ]);
        }

        // Future/proprietary tools do not need to be hard-coded in the catalog.
        // A domain-specific phrase such as "POS system" can be assessed from a
        // concrete usage statement, while the bare word "system" remains too vague.
        if (!$requiredTools && !$genericCategories && $directSource !== '') {
            $domainTerms = $this->matchingDistinctiveTerms($baseRequirement, $directSource);
            $usageSignal = (bool) preg_match('/\b(used|using|use|operated|operate|worked\s+with|experience\s+with|experience\s+using|proficient\s+(?:in|with)|familiar\s+with|handled|configured|administered|gumamit|ginamit|nag-operate|nagoperate)\b/i', $directSource);
            $phraseEvidence = $this->phraseMatches($baseRequirement, $directSource);
            $domainSystemPhrase = collect($domainTerms)->contains(function ($term) use ($baseRequirement, $directSource) {
                $quoted = preg_quote($term, '/');
                return preg_match('/\b' . $quoted . '\s+(?:system|software|tool|platform)\b/i', $baseRequirement)
                    && preg_match('/\b' . $quoted . '\s+(?:system|software|tool|platform)\b/i', $directSource);
            });

            if ($domainTerms && $usageSignal && ($phraseEvidence > 0 || $domainSystemPhrase || count($domainTerms) >= 2)) {
                return $this->qualificationResult('skill', 'matched',
                    'Direct domain-specific system/tool usage evidence found: ' . implode(', ', array_slice($domainTerms, 0, 6)) . '.',
                    count($domainTerms) + $phraseEvidence,
                    implode(', ', array_slice($domainTerms, 0, 6)),
                    null,
                    [
                        'preferred_bonus' => $preferredBonus,
                        'preferred_bonus_matched' => $preferredBonusMatched,
                        'evidence_source' => 'Role-specific exact tools / comparable-work evidence',
                    ]
                );
            }
            if ($domainTerms && $usageSignal) {
                return $this->qualificationResult('skill', 'needs_verification',
                    'A domain-specific system/tool usage signal was found (' . implode(', ', array_slice($domainTerms, 0, 4)) . '), but the evidence is too limited to establish the requirement confidently.',
                    count($domainTerms),
                    implode(', ', array_slice($domainTerms, 0, 4)),
                    null,
                    [
                        'preferred_bonus' => $preferredBonus,
                        'preferred_bonus_matched' => $preferredBonusMatched,
                        'evidence_source' => 'Role-specific exact tools / comparable-work evidence',
                    ]
                );
            }
        }

        $genericClaim = $source !== '' && preg_match('/\b(accounting\s+(?:system|software)|erp\s+(?:system|software)?|financial\s+(?:system|software)|used\s+(?:a\s+)?system|software\s+experience|system\s+experience)\b/i', $source);
        if ($genericClaim && ($allowsSimilar || !$requiredTools)) {
            return $this->qualificationResult('skill', 'needs_verification',
                'The applicant claims relevant system/software experience but did not name a platform clearly enough to verify against the vacancy.',
                null, 'Generic system claim', null, [
                    'preferred_bonus' => $preferredBonus,
                    'preferred_bonus_matched' => $preferredBonusMatched,
                    'evidence_source' => 'Role-specific exact tools / comparable-work evidence',
                ]);
        }

        if ($this->trainingGapMatchesRequirement($text, $trainingGap)) {
            return $this->qualificationResult('skill', 'needs_verification',
                'The applicant identified this system/tool area as a training need; no sufficiently specific positive evidence of proficiency was found.',
                null, 'Training gap disclosed', null, [
                    'preferred_bonus' => $preferredBonus,
                    'preferred_bonus_matched' => $preferredBonusMatched,
                    'evidence_source' => 'Training gap answer',
                ]);
        }

        $detail = $evidenceTools
            ? 'Named tools were submitted (' . implode(', ', $evidenceTools) . '), but none establish the required system/tool match.'
            : 'No named system/tool evidence was found. A generic word such as "system" does not satisfy this requirement.';

        return $this->qualificationResult('skill', 'not_evidenced', $detail, null, 'No qualifying tool evidence', null, [
            'preferred_bonus' => $preferredBonus,
            'preferred_bonus_matched' => $preferredBonusMatched,
            'evidence_source' => $source !== '' ? 'Role-specific skills / tools evidence' : null,
        ]);
    }

    /** @return array{key:string,label:string}|null */
    private function behavioralCompetencyForRequirement(string $text): ?array
    {
        $map = [
            'attention_detail' => ['label' => 'Attention to detail / accuracy', 'needles' => ['attention to detail','detail-oriented','detail oriented','accuracy','accurate']],
            'communication' => ['label' => 'Communication', 'needles' => ['communication','communicate','verbal','written communication']],
            'teamwork' => ['label' => 'Teamwork / collaboration', 'needles' => ['teamwork','team player','collaboration','collaborative','work with a team']],
            'problem_solving' => ['label' => 'Problem solving', 'needles' => ['problem solving','problem-solving','analytical thinking','critical thinking']],
            'organization' => ['label' => 'Organization / time management', 'needles' => ['organized','organization','time management','prioritization','prioritize','deadline management']],
            'leadership' => ['label' => 'Leadership', 'needles' => ['leadership','lead team','supervision','supervisory']],
            'customer_service' => ['label' => 'Customer service', 'needles' => ['customer service','client service','customer handling','client handling']],
            'adaptability' => ['label' => 'Adaptability / learning agility', 'needles' => ['adaptability','adaptable','fast learner','willing to learn','learning agility']],
        ];
        $lower = Str::lower($text);
        foreach ($map as $key => $row) {
            foreach ($row['needles'] as $needle) {
                if (Str::contains($lower, $needle)) {
                    return ['key' => $key, 'label' => $row['label']];
                }
            }
        }
        return null;
    }

    private function behavioralEvidenceSignalCount(string $key, string $text): int
    {
        $patterns = [
            'attention_detail' => [
                '/\b(reviewed|checked|verified|validated|reconciled|compared|cross[- ]?checked|audited|proofread|inspected)\b/i',
                '/\b(nireview|chineck|nagcheck|nag-check|kinumpara|sinuri|tiningnan|tinignan|inisa-isa|beripika|bineripika|nireconcile|ni-reconcile|itinama|naitama)\b/i',
                '/\b(discrepanc(?:y|ies)|mismatch|error|errors|accuracy|tugma|hindi nagtutugma)\b/i',
            ],
            'communication' => [
                '/\b(communicated|explained|presented|reported|coordinated|discussed|clarified|informed|briefed|wrote|documented)\b/i',
                '/\b(ipinaliwanag|nagpaliwanag|kinausap|nakipag-usap|iniulat|nireport|nakipag-coordinate|nag-coordinate|nilinaw)\b/i',
            ],
            'teamwork' => [
                '/\b(collaborated|coordinated|partnered|assisted|supported|worked with|helped|team)\b/i',
                '/\b(nakipagtulungan|tumulong|tinulungan|kasama ang team|nakipag-coordinate|nag-coordinate)\b/i',
            ],
            'problem_solving' => [
                '/\b(analyzed|investigated|troubleshot|resolved|fixed|identified|traced|reconciled|corrected|verified|compared|checked|reviewed|found|isolated|root cause|solution)\b/i',
                '/\b(sinuri|inimbestigahan|nilutas|nalutas|inayos|natukoy|nahanap|pinag-aralan|kinumpara|chineck|nireview|bineripika|nireconcile|itinama|naitama)\b/i',
            ],
            'organization' => [
                '/\b(organized|planned|prioritized|scheduled|tracked|monitored|deadline|checklist)\b/i',
                '/\b(inorganisa|pinlano|inuna|prioritize|minonitor|sinubaybayan|iskedyul)\b/i',
            ],
            'leadership' => [
                '/\b(led|supervised|delegated|coached|mentored|trained|directed|managed a team)\b/i',
                '/\b(nanguna|namahala|nagsupervise|nag-supervise|nagturo|tinuruan|nagdelegate|nag-delegate)\b/i',
            ],
            'customer_service' => [
                '/\b(customer|client|complaint|inquiry|concern)\b/i',
                '/\b(assisted|handled|resolved|responded|served|followed up)\b/i',
                '/\b(customer|kliyente|reklamo|concern).{0,40}\b(tinulungan|hinandle|inayos|sinagot|niresolba)\b/i',
            ],
            'adaptability' => [
                '/\b(adapted|adjusted|learned|self-taught|trained|new process|new system)\b/i',
                '/\b(nag-adjust|umangkop|natutunan|pinag-aralan|nag-aral|bagong proseso|bagong system)\b/i',
            ],
        ];
        return $this->patternSignalCount($text, $patterns[$key] ?? []);
    }

    private function evaluateBehavioralQualification(string $requirement, array $competency, array $answers): array
    {
        $source = $this->positiveQualificationEvidenceText($answers);
        $trainingGap = trim((string) ($answers['role_training_gap'] ?? ''));
        if ($source === '') {
            return $this->qualificationResult('skill', 'not_evidenced',
                'No concrete evidence was submitted for ' . $competency['label'] . '.', null, 'No behavioral evidence', null);
        }

        $signals = $this->behavioralEvidenceSignalCount($competency['key'], $source);
        $claimTerms = $this->matchingDistinctiveTerms($requirement, $source);
        $minimumSignals = in_array($competency['key'], ['attention_detail','customer_service'], true) ? 2 : 1;
        if ($signals >= $minimumSignals) {
            return $this->qualificationResult('skill', 'matched',
                $competency['label'] . ' is supported by ' . $signals . ' concrete behavior/context signal(s), not only by a self-description.',
                $signals, $signals . ' behavioral evidence signal(s)', null, [
                    'evidence_source' => 'Role examples / duties / problem-solving answer',
                ]);
        }

        if ($signals > 0 || $claimTerms || $this->trainingGapMatchesRequirement($requirement, $trainingGap)) {
            return $this->qualificationResult('skill', 'needs_verification',
                'The applicant mentioned or partially demonstrated ' . $competency['label'] . ', but the submitted evidence is not specific enough to treat it as established. Verify with a concrete interview example.',
                $signals ?: (count($claimTerms) ?: null),
                $signals > 0 ? $signals . ' partial behavior signal(s)' : 'Claim present; example not established',
                null,
                ['evidence_source' => 'Role-focused questionnaire']
            );
        }

        return $this->qualificationResult('skill', 'not_evidenced',
            'No sufficiently specific evidence was found for ' . $competency['label'] . '.', null, 'No behavioral evidence', null);
    }

    private function trainingGapMatchesRequirement(string $requirement, string $trainingGap): bool
    {
        if (trim($trainingGap) === '') {
            return false;
        }
        if (array_intersect($this->toolNamesInText($requirement), $this->toolNamesInText($trainingGap))) {
            return true;
        }
        return count($this->matchingDistinctiveTerms($requirement, $trainingGap)) > 0;
    }

    private function explicitLackOfRequirement(string $requirement, string $evidence): bool
    {
        if (!preg_match('/\b(no\s+experience|no\s+[a-z0-9 .+#-]{1,60}\s+experience|without\s+(?:[a-z0-9 .+#-]{1,60}\s+)?experience|do\s+not\s+(?:yet\s+)?have\s+(?:[a-z0-9 .+#-]{1,60}\s+)?experience|don\'t\s+(?:yet\s+)?have\s+(?:[a-z0-9 .+#-]{1,60}\s+)?experience|not\s+yet\s+experienced\s+(?:in|with)|have\s+not\s+(?:yet\s+)?handled|not\s+familiar|not\s+proficient|wala\s+(?:pa\s+)?akong\s+(?:[a-z0-9 .+#-]{1,60}\s+)?experience|wala\s+(?:pa\s+)?(?:[a-z0-9 .+#-]{1,60}\s+)?experience|hindi\s+ako\s+(?:marunong|familiar))\b/i', $evidence)) {
            return false;
        }
        return count($this->matchingDistinctiveTerms($requirement, $evidence)) > 0
            || (bool) array_intersect($this->toolNamesInText($requirement), $this->toolNamesInText($evidence));
    }

    /** @return array<int,string> */
    private function matchingDistinctiveTerms(string $requirement, string $evidence): array
    {
        $generic = [
            'system','systems','software','tool','tools','attention','detail','details','accuracy','accurate','quality',
            'support','responsibility','responsibilities','officer','associate','assistant','staff','specialist','manager','management',
            'team','company','knowledge','ability','abilities','task','tasks','process','processes','service','services',
        ];
        $required = $this->qualificationSpecificTerms($requirement, $generic);
        return array_values(array_intersect($required, $this->terms($evidence)));
    }

    /** @return array<int,string> */
    private function matchedSkillConcepts(string $requirement, string $evidence): array
    {
        $concepts = [
            'Accounting / bookkeeping' => ['accounting','accountancy','bookkeeping','bookkeeper'],
            'Accounts payable' => ['accounts payable','account payable','a/p','ap processing','payables'],
            'Accounts receivable' => ['accounts receivable','account receivable','a/r','ar collection','receivables'],
            'Reconciliation' => ['reconciliation','reconcile','reconciled','bank reconciliation'],
            'General ledger / journal entries' => ['general ledger','journal entry','journal entries','ledger posting'],
            'Financial reporting' => ['financial report','financial reporting','financial statements','income statement','balance sheet'],
            'Payroll' => ['payroll','payroll processing'],
            'Audit / compliance' => ['audit','auditing','compliance','internal control'],
            'Tax' => ['tax','taxation','bir filing','tax filing'],
            'Inventory management' => ['inventory management','inventory control','stock monitoring','stock management'],
            'Sales' => ['sales','selling','sales target','lead generation','prospecting'],
            'Customer service' => ['customer service','customer support','client service','customer handling'],
            'Digital marketing' => ['digital marketing','facebook ads','meta ads','social media marketing','campaign management'],
            'Content creation' => ['content creation','content planning','copywriting','video content'],
            'Graphic design' => ['graphic design','visual design','layout design'],
            'Video editing' => ['video editing','video editor','motion graphics'],
            'IT support / troubleshooting' => ['it support','troubleshooting','computer repair','hardware troubleshooting','help desk','helpdesk'],
            'Windows support' => ['windows troubleshooting','windows support','windows 10','windows 11','microsoft windows'],
            'Hardware / endpoint support' => ['hardware support','hardware/software support','hardware troubleshooting','hardware diagnostics','hardware replacement','desktop support','laptop support'],
            'Networking' => ['networking','network configuration','basic networking','network support','lan','wan','router configuration','tcp/ip','connectivity'],
            'System administration' => ['system administration','systems administration','active directory administration','windows server administration','account administration'],
            'Documentation' => ['documentation','ticket documentation','ticket handling','knowledge base','documented'],
            'User support' => ['user support','user-support','end-user support','end user support','help desk support'],
            'Web development' => ['web development','web developer','frontend','front-end','backend','back-end'],
            'Database' => ['database','sql','data query','database administration'],
            'Data entry / records' => ['data entry','record keeping','records management','data encoding','records encoding'],
            'Warehouse / logistics' => ['warehouse','logistics','dispatch','shipping','receiving'],
            'Recruitment / HR' => ['recruitment','hiring','talent acquisition','human resources','hr administration'],
        ];
        $req = Str::lower($requirement);
        $ev = Str::lower($evidence);
        $matched = [];
        foreach ($concepts as $label => $patterns) {
            $reqHas = collect($patterns)->contains(fn ($pattern) => Str::contains($req, $pattern));
            $evHas = collect($patterns)->contains(fn ($pattern) => Str::contains($ev, $pattern));
            if ($reqHas && $evHas) {
                $matched[] = $label;
            }
        }
        return array_values(array_unique($matched));
    }

    /**
     * Conservative role-title family match used only for employment-title relevance.
     * Generic words such as "support" are too weak on their own, but a recognized
     * title family (e.g. IT Support / Help Desk for an IT Specialist vacancy) is a
     * strong and explainable role-relevance signal.
     */
    private function roleTitleFamilySignals(string $roleContext, string $positionTitle): int
    {
        $context = Str::lower($roleContext);
        $title = Str::lower($positionTitle);
        if ($context === '' || $title === '') {
            return 0;
        }

        $families = [
            [
                'context' => '/\b(?:it\s+support|it\s+specialist|help\s*desk|service\s*desk|system\s+administration|systems?\s+administrator|technical\s+support)\b/i',
                'strong' => '/\b(?:it\s+support|help\s*desk|service\s*desk|systems?\s+administrator|technical\s+support)\b/i',
                'adjacent' => '/\b(?:computer\s+technician|desktop\s+support|computer\s+support|network\s+technician)\b/i',
            ],
            [
                'context' => '/\b(?:accounting|accountant|accountancy|bookkeeping|finance)\b/i',
                'strong' => '/\b(?:accountant|accounting\s+officer|accounting\s+associate|bookkeeper|accounts?\s+(?:payable|receivable)|finance\s+officer)\b/i',
                'adjacent' => '/\b(?:billing|payroll|audit|finance\s+assistant)\b/i',
            ],
            [
                'context' => '/\b(?:human\s+resources|hr\s+|recruitment|talent\s+acquisition)\b/i',
                'strong' => '/\b(?:hr\s+|human\s+resources|recruiter|recruitment|talent\s+acquisition)\b/i',
                'adjacent' => '/\b(?:admin(?:istrative)?\s+assistant|office\s+administrator)\b/i',
            ],
            [
                'context' => '/\b(?:marketing|social\s+media|content|digital\s+marketing)\b/i',
                'strong' => '/\b(?:marketing|social\s+media|content\s+(?:creator|specialist)|digital\s+marketing)\b/i',
                'adjacent' => '/\b(?:graphic\s+designer|video\s+editor|communications?)\b/i',
            ],
            [
                'context' => '/\b(?:warehouse|logistics|inventory|dispatch)\b/i',
                'strong' => '/\b(?:warehouse|logistics|inventory|dispatch|stock\s+controller)\b/i',
                'adjacent' => '/\b(?:receiving|shipping|storekeeper)\b/i',
            ],
        ];

        foreach ($families as $family) {
            if (!preg_match($family['context'], $context)) {
                continue;
            }
            if (preg_match($family['strong'], $title)) {
                return 2;
            }
            if (preg_match($family['adjacent'], $title)) {
                return 1;
            }
        }
        return 0;
    }

    private function experienceRoleContext(JobVacancy $vacancy): string
    {
        $context = trim(($vacancy->title ?? '') . ' ' . ($vacancy->position?->name ?? ''));
        foreach ($this->qualificationRequirements($vacancy) as $requirement) {
            $text = (string) ($requirement->qualification_text ?? '');
            if ($this->inferQualificationType((string) ($requirement->qualification_type ?? 'auto'), $text) === 'experience') {
                $context .= ' ' . $text;
            }
        }
        return trim($context);
    }

    private function experienceRoleTerms(JobVacancy $vacancy): array
    {
        return $this->distinctiveRoleTermsFromText($this->experienceRoleContext($vacancy));
    }

    /** @return array<int,string> */
    private function distinctiveRoleTermsFromText(string $text): array
    {
        $generic = [
            'degree','bachelor','graduate','college','course','field','education','year','years','month','months',
            'required','preferred','advantage','minimum','least','position','vacancy','candidate','applicant',
            'system','systems','software','tool','tools','attention','detail','details','accuracy','accurate','quality',
            'officer','associate','assistant','staff','specialist','manager','management','team','company','support',
            'responsibility','responsibilities','process','processes','task','tasks','service','services',
        ];
        return $this->qualificationSpecificTerms($text, $generic);
    }

    private function patternSignalCount(string $text, array $patterns): int
    {
        $count = 0;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $count++;
            }
        }
        return $count;
    }

    private function roleContext(JobVacancy $vacancy): string
    {
        // Use both the legacy qualification text and the normalized qualification
        // records. Assessment should not lose role terms when HR edits only the
        // structured qualification list or when an older vacancy has stale free text.
        $structuredQualifications = $this->qualificationRequirements($vacancy)
            ->pluck('qualification_text')
            ->map(fn ($text) => trim((string) $text))
            ->filter()
            ->implode(' ');

        return implode(' ', array_filter([
            $vacancy->title,
            $vacancy->position?->name,
            $vacancy->description,
            $vacancy->qualifications,
            $structuredQualifications,
        ]));
    }

    /**
     * Context allowed to influence Role Fit supporting scores. HR's required vacancy
     * qualifications are the source of truth; optional qualifications are intentionally
     * excluded so a nice-to-have cannot quietly become a hard scoring penalty elsewhere.
     */
    private function roleFitContext(JobVacancy $vacancy): string
    {
        $required = $this->qualificationRequirements($vacancy)
            ->filter(function ($requirement) {
                $text = (string) ($requirement->qualification_text ?? '');
                return $this->effectiveRequirementLevel(
                    (string) ($requirement->requirement_level ?? ''),
                    $text
                ) === 'required';
            })
            ->pluck('qualification_text')
            ->map(fn ($text) => trim((string) $text))
            ->filter()
            ->implode(' ');

        $base = trim(implode(' ', array_filter([
            $vacancy->title,
            $vacancy->position?->name,
            $required,
        ])));

        // Legacy fallback: if a vacancy has no structured/required qualification rows,
        // retain the older role context rather than returning an empty scoring context.
        return $required !== '' ? $base : $this->roleContext($vacancy);
    }

    /** Context for optional evidence display/tie-breaking only; never direct Role Fit. */
    private function optionalRoleContext(JobVacancy $vacancy): string
    {
        return $this->qualificationRequirements($vacancy)
            ->filter(function ($requirement) {
                $text = (string) ($requirement->qualification_text ?? '');
                return $this->effectiveRequirementLevel(
                    (string) ($requirement->requirement_level ?? ''),
                    $text
                ) !== 'required';
            })
            ->pluck('qualification_text')
            ->map(fn ($text) => trim((string) $text))
            ->filter()
            ->implode(' ');
    }

    private function roleSpecificTerms(JobVacancy $vacancy): array
    {
        return $this->distinctiveRoleTermsFromText($this->roleFitContext($vacancy));
    }

    /**
     * Optional evidence breadth is a transparent tie-breaker only. The returned
     * percentage is literal coverage of the named optional technologies/components,
     * not a fit score. Example: 1 of 4 named technologies = 25, not an artificial
     * 70+ baseline. Unverified optional evidence receives no ranking advantage until
     * HR has enough evidence to treat the optional item as matched.
     *
     * @return array{0:int,1:string}
     */
    private function optionalPreferenceEvidenceStrength(string $text, array $evaluation, string $status): array
    {
        if ($status === 'not_matched' || $status === 'not_evidenced') {
            return [0, 'No optional advantage evidenced'];
        }
        if ($status === 'needs_verification') {
            return [0, 'Optional evidence needs verification before it can be used as a tie-breaker'];
        }

        $namedTools = array_values(array_unique($this->toolNamesInText($text)));
        $matchedCount = max(0, (int) ($evaluation['metric'] ?? 0));
        if (count($namedTools) > 1) {
            $matchedCount = min(count($namedTools), $matchedCount);
            $ratio = count($namedTools) > 0 ? $matchedCount / count($namedTools) : 0;
            return [
                (int) round($ratio * 100),
                $matchedCount . '/' . count($namedTools) . ' named optional technologies evidenced',
            ];
        }
        if (count($namedTools) === 1) {
            return [100, '1/1 named optional technology evidenced'];
        }

        $components = array_values((array) ($evaluation['component_evidence'] ?? []));
        if (count($components) > 1) {
            $matchedComponents = collect($components)->where('status', 'matched')->count();
            $ratio = count($components) > 0 ? $matchedComponents / count($components) : 0;
            return [
                (int) round($ratio * 100),
                $matchedComponents . '/' . count($components) . ' optional components evidenced',
            ];
        }

        return [100, 'Preferred/nice-to-have qualification evidenced'];
    }

    private function meaningfulOverlap(string $requirement, string $evidence, array $extraStop = []): int
    {
        $required = $this->qualificationSpecificTerms($requirement, $extraStop);
        $evidenceTerms = $this->terms($evidence);
        return count(array_intersect(array_unique($required), array_unique($evidenceTerms)));
    }

    private function qualificationSpecificTerms(string $text, array $extraStop = []): array
    {
        $stop = array_merge([
            'and','or','the','a','an','of','to','in','with','for','is','are','be','at','least','minimum','required','preferred',
            'knowledge','skill','skills','experience','experienced','proficient','proficiency','good','strong','ability','related','relevant',
            'work','working','candidate','applicant','must','should','have','has','having','role','position','similar','using','use',
        ], $extraStop);

        return array_values(array_filter(
            $this->terms($text),
            fn ($term) => strlen($term) >= 3 && !in_array($term, $stop, true)
        ));
    }

    /**
     * Structured credential evidence grouped by current verification state.
     * Legacy V4 textarea answers are preserved as status-unknown records so an
     * older application is never silently promoted to a verified credential.
     *
     * @return array{all_text:string,valid_text:string,pending_text:string,expired_text:string,unknown_text:string,record_summaries:array<int,string>,records:array<int,array<string,string>>,professional_declaration:string,training_declaration:string}
     */
    private function credentialEvidenceBundle(array $answers): array
    {
        $records = [];
        $professionalDeclaration = Str::lower(trim((string) ($answers['professional_credential_declaration'] ?? '')));
        $trainingDeclaration = Str::lower(trim((string) ($answers['certification_training_declaration'] ?? '')));

        for ($i = 1; $i <= 2; $i++) {
            $nameKey = $i === 1 ? 'professional_qualifications' : 'professional_qualification_name_2';
            $record = [
                'kind' => 'professional',
                'name' => trim((string) ($answers[$nameKey] ?? '')),
                'type' => trim((string) ($answers["professional_qualification_type_{$i}"] ?? '')),
                'issuer' => trim((string) ($answers["professional_qualification_issuer_{$i}"] ?? '')),
                'status' => trim((string) ($answers["professional_qualification_status_{$i}"] ?? '')),
                'date' => trim((string) ($answers["professional_qualification_valid_until_{$i}"] ?? '')),
                'credential' => trim((string) ($answers["professional_qualification_credential_{$i}"] ?? '')),
            ];
            if (collect($record)->except('kind')->filter(fn ($value) => trim((string) $value) !== '')->isNotEmpty()) {
                $records[] = $record;
            }
        }

        for ($i = 1; $i <= 2; $i++) {
            $nameKey = $i === 1 ? 'relevant_certifications' : 'certification_name_2';
            $record = [
                'kind' => 'training',
                'name' => trim((string) ($answers[$nameKey] ?? '')),
                'type' => trim((string) ($answers["certification_type_{$i}"] ?? '')),
                'issuer' => trim((string) ($answers["certification_provider_{$i}"] ?? '')),
                'status' => trim((string) ($answers["certification_status_{$i}"] ?? '')),
                'date' => trim((string) ($answers["certification_completion_date_{$i}"] ?? '')),
                'credential' => trim((string) ($answers["certification_credential_{$i}"] ?? '')),
            ];
            if (collect($record)->except('kind')->filter(fn ($value) => trim((string) $value) !== '')->isNotEmpty()) {
                $records[] = $record;
            }
        }

        $groups = ['valid' => [], 'pending' => [], 'expired' => [], 'unknown' => []];
        $summaries = [];

        foreach ($records as $record) {
            $statusLower = Str::lower($record['status']);
            if (Str::contains($statusLower, 'expired')) {
                $state = 'expired';
            } elseif (Str::contains($statusLower, ['pending', 'in progress', 'in process'])) {
                $state = 'pending';
            } elseif (Str::contains($statusLower, ['active', 'valid', 'completed', 'passed'])) {
                $state = 'valid';
            } else {
                $state = 'unknown';
            }

            // An Active/Valid professional credential with an explicitly past
            // valid-until date is treated as expired even if the selected status was
            // stale. The form validator also blocks this for new applications.
            if ($record['kind'] === 'professional' && $record['date'] !== '') {
                $date = $this->parseDate($record['date']);
                if ($date && $date->lt(now()->startOfDay()) && $state === 'valid') {
                    $state = 'expired';
                }
            }

            $recordText = trim(implode(' ', array_filter([
                $record['name'], $record['type'], $record['issuer'], $record['status'], $record['date'],
            ])));
            if ($recordText !== '') {
                $groups[$state][] = $recordText;
            }

            $summary = $record['name'] !== '' ? $record['name'] : ($record['type'] ?: 'Credential');
            if ($record['issuer'] !== '') {
                $summary .= ' — ' . $record['issuer'];
            }
            $summary .= ' (' . ($record['status'] ?: 'status not provided') . ')';
            $summaries[] = $summary;
        }

        return [
            'all_text' => trim(implode(' ', array_merge($groups['valid'], $groups['pending'], $groups['expired'], $groups['unknown']))),
            'valid_text' => trim(implode(' ', $groups['valid'])),
            'pending_text' => trim(implode(' ', $groups['pending'])),
            'expired_text' => trim(implode(' ', $groups['expired'])),
            'unknown_text' => trim(implode(' ', $groups['unknown'])),
            'record_summaries' => $summaries,
            'records' => $records,
            'professional_declaration' => $professionalDeclaration,
            'training_declaration' => $trainingDeclaration,
        ];
    }

    private function credentialRequirementExplicitlyDeclined(string $requirement, array $bundle): bool
    {
        $professionalNo = ($bundle['professional_declaration'] ?? '') === 'no';
        $trainingNo = ($bundle['training_declaration'] ?? '') === 'no';
        if ($professionalNo && $trainingNo) {
            return true;
        }

        $lower = Str::lower($requirement);
        $professionalLike = (bool) preg_match('/\b(license|licensed|licensure|registration|registered|board|prc|cpa|cma|cia|cisa|national\s+certificate|nc\s*(?:ii|iii|iv|2|3|4))\b/i', $lower);
        $trainingLike = (bool) preg_match('/\b(certification|certificate|certified|training|seminar|workshop|technical\s+course)\b/i', $lower);

        return ($professionalLike && $professionalNo)
            || ($trainingLike && !$professionalLike && $trainingNo);
    }

    /** @return array<int,string> */
    private function certificationIdentifiers(string $text): array
    {
        $patterns = [
            'CPA' => '/\b(?:cpa|certified\s+public\s+accountant)\b/i',
            'CMA' => '/\b(?:cma|certified\s+management\s+accountant)\b/i',
            'CIA' => '/\b(?:cia|certified\s+internal\s+auditor)\b/i',
            'CISA' => '/\b(?:cisa|certified\s+information\s+systems\s+auditor)\b/i',
            'CCNA' => '/\bccna\b/i',
            'CompTIA A+' => '/\bcomptia\s+a\+\b/i',
            'NC II' => '/\b(?:nc\s*(?:ii|2)|national\s+certificate\s+(?:ii|2))\b/i',
            'NC III' => '/\b(?:nc\s*(?:iii|3)|national\s+certificate\s+(?:iii|3))\b/i',
            'NC IV' => '/\b(?:nc\s*(?:iv|4)|national\s+certificate\s+(?:iv|4))\b/i',
            'PRC License' => '/\bprc\s+(?:license|licensed|registration)\b/i',
            "Driver's License" => '/\b(?:driver(?:\'s)?\s+license|professional\s+driver(?:\'s)?\s+license|non-professional\s+driver(?:\'s)?\s+license)\b/i',
        ];
        $found = [];
        foreach ($patterns as $label => $pattern) {
            if (preg_match($pattern, $text)) {
                $found[] = $label;
            }
        }
        return $found;
    }

    /** @return array<int,string> */
    private function educationDisciplineTags(string $text): array
    {
        $lower = Str::lower($text);
        $tags = [];

        $patterns = [
            'accounting' => ['accountancy','accounting','accounting technology','management accounting','bookkeeping'],
            'finance' => ['finance','financial management','financial accounting','banking and finance'],
            'economics' => ['economics','business economics','economy'],
            'commerce' => ['commerce','business commerce'],
            'information_technology' => ['information technology','bsit','computer science','computer information','information systems','information system'],
            'marketing' => ['marketing','advertising'],
            'business' => ['business administration','bsba','entrepreneurship','business management'],
            'human_resources' => ['human resource','human resources','hr management'],
            'psychology' => ['psychology','behavioral science'],
            'engineering_mechanical' => ['mechanical engineering','mechanical engineer'],
            'engineering_electrical' => ['electrical engineering','electrical engineer','electronics engineering','electronics engineer'],
            'engineering_civil' => ['civil engineering','civil engineer'],
            'engineering_industrial' => ['industrial engineering','industrial engineer'],
            'engineering_computer' => ['computer engineering','computer engineer'],
            'education' => ['education','teaching','teacher education'],
            'communications' => ['communication','communications','mass communication'],
        ];

        foreach ($patterns as $tag => $needles) {
            foreach ($needles as $needle) {
                if (Str::contains($lower, $needle)) {
                    $tags[] = $tag;
                    break;
                }
            }
        }

        return array_values(array_unique($tags));
    }

    private function namedToolMatches(JobVacancy $vacancy, string $evidence): int
    {
        return count($this->namedToolMatchNames($vacancy, $evidence));
    }

    /** @return array<int,string> */
    private function namedToolMatchNames(JobVacancy $vacancy, string $evidence): array
    {
        $context = $this->roleContext($vacancy);
        return $this->relevantToolMatches(
            $this->toolNamesInText($context),
            $this->toolNamesInText($evidence),
            $context
        );
    }

    private function phraseMatches(string $requirement, string $evidence): int
    {
        $reqTerms = $this->orderedMeaningfulTerms($requirement);
        $evidenceTerms = $this->orderedMeaningfulTerms($evidence);
        if (count($reqTerms) < 2 || count($evidenceTerms) < 2) {
            return 0;
        }

        $evidenceBigrams = [];
        for ($i = 0; $i < count($evidenceTerms) - 1; $i++) {
            $evidenceBigrams[] = $evidenceTerms[$i] . ' ' . $evidenceTerms[$i + 1];
        }
        $count = 0;
        for ($i = 0; $i < count($reqTerms) - 1; $i++) {
            if (in_array($reqTerms[$i] . ' ' . $reqTerms[$i + 1], $evidenceBigrams, true)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Phrase matching must preserve word order. terms() intentionally de-duplicates
     * tokens for set-overlap calculations, so using it for bigrams can manufacture
     * phrases that were never present in the original sentence.
     *
     * @return array<int,string>
     */
    private function orderedMeaningfulTerms(string $text): array
    {
        $stop = [
            'the','and','for','with','that','this','from','your','you','are','our','will','have','has','job','work','role','must',
            'can','able','into','about','position','candidate','applicant','required','preferred','related','relevant','good','strong',
            'knowledge','skill','skills','experience','experienced','proficient','proficiency','using','use','similar','least','minimum',
        ];
        $words = preg_split('/[^a-z0-9+#.]+/i', Str::lower(strip_tags($text))) ?: [];

        return array_values(array_filter(
            $words,
            fn ($word) => strlen($word) >= 3 && !in_array($word, $stop, true)
        ));
    }

    private function situationSignalCount(string $text): int
    {
        $patterns = [
            '/\b(problem|issue|challenge|situation|error|errors|delay|complaint|shortage|failed|failure|discrepanc(?:y|ies)|mismatch|difference)\b/i',
            '/\b(problema|isyu|hamon|reklamo|kakulangan|kulang|aberya|mali|pagkakamali|hindi\s+nagtutugma|hindi\s+tugma|may\s+problema)\b/i',
            '/\b(unexpected|urgent|deadline|backlog|duplicate|missing|incorrect|incomplete)\b/i',
        ];
        return $this->patternSignalCount($text, $patterns);
    }

    private function actionSignalCount(string $text): int
    {
        $patterns = [
            '/\b(reviewed|review|nireview|ni-review|nagreview|nag-review)\b/i',
            '/\b(compared|compare|cross[- ]?checked|kinumpara|nagkumpara|nag-compare|nagcompare)\b/i',
            '/\b(checked|verified|validated|inspected|audited|reconciled|chineck|nagcheck|nag-check|bineripika|beripika|tiningnan|tinignan|inisa-isa|nireconcile|ni-reconcile)\b/i',
            '/\b(analyzed|investigated|troubleshot|diagnosed|identified|sinuri|inimbestigahan|pinag-aralan|nagsuri|nag-analyze|inanalyze)\b/i',
            '/\b(resolved|fixed|corrected|adjusted|updated|repaired|inayos|nilutas|naitama|itinama|binago|niresolve|ni-resolve)\b/i',
            '/\b(created|built|developed|designed|prepared|processed|implemented|configured|set up|ginawa|gumawa|binuo|dinisenyo|inihanda|pinroseso|inimplement|nag-setup|nagsetup)\b/i',
            '/\b(coordinated|communicated|explained|reported|discussed|clarified|nakipag-coordinate|nag-coordinate|kinausap|ipinaliwanag|nagpaliwanag|iniulat|nireport|nilinaw)\b/i',
            '/\b(managed|led|supervised|trained|supported|handled|pinamahalaan|namahala|nanguna|nagsupervise|nag-supervise|tinuruan|sinuportahan|hinandle|nag-handle|naghandle)\b/i',
            '/\b(monitored|planned|organized|prioritized|scheduled|tracked|minonitor|pinlano|inorganisa|inuna|sinubaybayan)\b/i',
            '/\b(automated|tested|negotiated|documented|completed|inautomate|sinubukan|nakipag-negotiate|dinocument|tinapos)\b/i',
        ];
        return $this->patternSignalCount($text, $patterns);
    }

    private function resultSignalCount(string $text): int
    {
        // Count outcome categories, not repeated words. This keeps verbose answers from
        // inflating the score while recognizing natural English and Filipino/Taglish
        // outcome language that previously displayed as "0 result signals".
        $patterns = [
            // Resolution / restoration / recovery.
            '/\b(resolved|fixed|corrected|reconciled|restored|recovered|repaired|returned\s+to\s+normal|back\s+to\s+normal|working\s+again|operational\s+again|nalutas|naayos|naitama|nareconcile|naibalik|bumalik\s+sa\s+normal|gumana\s+ulit|naging\s+maayos)\b/i',
            // Finding / confirmation of the cause or answer.
            '/\b(identified|found|determined|confirmed|pinpointed|isolated|natukoy|nahanap|nalaman|nakumpirma|natunton)\b/i',
            // Improvement / reduction / efficiency / accuracy.
            '/\b(improved|reduced|increased|saved|faster|more\s+accurate|more\s+stable|stabilized|optimized|napabuti|bumuti|nabawasan|nadagdagan|nakatipid|mas\s+mabilis|mas\s+tama|naging\s+stable|mas\s+maayos)\b/i',
            // Completion / success / continuity of work.
            '/\b(completed|achieved|successful|successfully|finished|resumed|continued\s+work|able\s+to\s+continue|work\s+continued|service\s+resumed|natapos|nakamit|matagumpay|tagumpay|nakapagpatuloy|nagpatuloy\s+ang\s+trabaho)\b/i',
            // Quality / clean outcome.
            '/\b(accuracy|accurate|matched|balanced|zero\s+errors?|no\s+errors?|without\s+errors?|tugma|nagtugma|balanse|walang\s+error|walang\s+mali)\b/i',
            // Prevention / stability / non-recurrence.
            '/\b(prevented|prevented\s+recurrence|did\s+not\s+recur|didn[\'’]?t\s+recur|no\s+recurrence|did\s+not\s+happen\s+again|remained\s+stable|stayed\s+stable|without\s+recurrence|naiwasan|hindi\s+na\s+naulit|hindi\s+na\s+bumalik|walang\s+pag-uulit|nanatiling\s+maayos|nanatiling\s+stable)\b/i',
            // Escalation avoided / user service restored.
            '/\b(without\s+escalation|no\s+escalation|did\s+not\s+require\s+escalation|user\s+was\s+able\s+to\s+continue|users?\s+were\s+able\s+to\s+continue|without\s+interruption|no\s+further\s+issue|walang\s+escalation|hindi\s+na\s+kinailangang\s+i-?escalate|nakapagpatuloy\s+ang\s+user)\b/i',
        ];
        $count = $this->patternSignalCount($text, $patterns);

        // A measurable result is an extra specificity category only when the number is
        // attached to a meaningful unit. Bare software versions never count here.
        if ($this->measurableEvidenceCount($text) > 0
            && preg_match('/\b(?:resolved|restored|reduced|improved|increased|saved|completed|achieved|accuracy|faster|users?|tickets?|devices?|incidents?|errors?|cases?|orders?|transactions?|natapos|nalutas|naayos|naibalik|nabawasan|napabuti)\b/i', $text)) {
            $count += 1;
        }
        return $count;
    }

    private function resultOutcomeContextCount(string $text): int
    {
        $patterns = [
            '/\b(?:same\s+day|same\s+shift|same\s+hour|before\s+the\s+deadline|within\s+\d+(?:\.\d+)?\s*(?:minutes?|hours?|days?|weeks?))\b/i',
            '/\b(?:for\s+the\s+following\s+(?:day|week|month|quarter)|for\s+the\s+next\s+(?:day|week|month|quarter)|during\s+the\s+following\s+(?:day|week|month)|during\s+the\s+next\s+(?:day|week|month))\b/i',
            '/\b(?:without\s+escalation|no\s+escalation|without\s+interruption|without\s+(?:restarting|errors?|failures?|issues?)|no\s+(?:restart|errors?|failures?|further\s+issue)|did\s+not\s+recur|didn[\'’]?t\s+recur|no\s+recurrence|remained\s+stable|stayed\s+stable)\b/i',
            '/\b(?:sa\s+parehong\s+araw|sa\s+loob\s+ng\s+\d+(?:\.\d+)?\s*(?:minuto|oras|araw|linggo)|bago\s+ang\s+deadline|hindi\s+na\s+naulit|walang\s+escalation|nanatiling\s+maayos|nanatiling\s+stable)\b/i',
        ];
        return $this->patternSignalCount($text, $patterns);
    }

    private function measurableEvidenceCount(string $text): int
    {
        $patterns = [
            // Percent/currency.
            '/\b\d+(?:\.\d+)?\s*(?:%|percent|percentage|php|pesos?|₱)\b/i',
            // Time / duration / SLA-style evidence.
            '/\b\d+(?:\.\d+)?\s*(?:minutes?|mins?|hours?|hrs?|days?|weeks?|months?|years?|minuto|oras|araw|linggo|buwan|taon)\b/i',
            // Workload / population / output / quality evidence.
            '/\b\d+(?:\.\d+)?\s*(?:users?|employees?|clients?|customers?|devices?|computers?|laptops?|workstations?|tickets?|incidents?|cases?|items?|transactions?|orders?|records?|entries?|errors?|issues?|requests?|branches?|locations?|departments?|accounts?)\b/i',
        ];
        return $this->patternSignalCount($text, $patterns);
    }

    private function containsAny(string $text, array $needles): bool
    {
        $lower = Str::lower($text);
        return collect($needles)->contains(fn ($needle) => Str::contains($lower, $needle));
    }

    private function mergedIntervalMonths(array $intervals): int
    {
        if (!$intervals) {
            return 0;
        }

        $normalized = collect($intervals)
            ->filter(fn ($range) => is_array($range) && count($range) >= 2 && $range[0] instanceof Carbon && $range[1] instanceof Carbon)
            ->map(fn ($range) => [$range[0]->copy()->startOfDay(), $range[1]->copy()->startOfDay()])
            ->sortBy(fn ($range) => $range[0]->getTimestamp())
            ->values();

        if ($normalized->isEmpty()) {
            return 0;
        }

        $merged = [];
        foreach ($normalized as [$start, $end]) {
            if (!$merged) {
                $merged[] = [$start, $end];
                continue;
            }
            $last = count($merged) - 1;
            if ($start->lte($merged[$last][1])) {
                if ($end->gt($merged[$last][1])) {
                    $merged[$last][1] = $end;
                }
            } else {
                $merged[] = [$start, $end];
            }
        }

        $months = 0;
        foreach ($merged as [$start, $end]) {
            $months += $start->diffInMonths($end);
        }
        return max(0, (int) floor($months));
    }

    private function availabilityMaximumDays(string $requirement): ?int
    {
        if (preg_match('/\bwithin\s+(\d+)\s*days?\b/i', $requirement, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/\bwithin\s+(\d+)\s*weeks?\b/i', $requirement, $m)) {
            return (int) $m[1] * 7;
        }
        if (preg_match('/\bwithin\s+(\d+)\s*months?\b/i', $requirement, $m)) {
            return (int) $m[1] * 30;
        }
        if (preg_match('/\b(?:maximum|max|at\s+most|no\s+more\s+than)\s+(\d+)\s*days?\b/i', $requirement, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    private function availabilityDays(string $availability, string $notice, string $exactStartDate = ''): ?int
    {
        $availabilityText = Str::lower(trim($availability));
        $noticeText = Str::lower(trim($notice));
        $exactStartDate = trim($exactStartDate);

        if ($availabilityText === '' && $noticeText === '' && $exactStartDate === '') {
            return null;
        }

        // Availability is the primary declaration of when the candidate can start.
        // "To be discussed" is intentionally unknown unless an exact date was also
        // supplied, in which case the exact date is the stronger evidence.
        if ($exactStartDate === '' && $availabilityText !== '' && Str::contains($availabilityText, 'to be discussed')) {
            return null;
        }

        $availabilityDays = $this->daysFromAvailabilityText($availabilityText, false);
        $noticeDays = $this->daysFromAvailabilityText($noticeText, true);
        $exactDays = null;
        if ($exactStartDate !== '') {
            $parsed = $this->parseDate($exactStartDate);
            if (!$parsed) {
                return null;
            }
            $exactDays = max(0, now()->startOfDay()->diffInDays($parsed->copy()->startOfDay(), false));
        }

        if ($availabilityText !== '' && $availabilityDays === null && $exactDays === null) {
            return null;
        }

        if ($availabilityDays === null && $noticeDays === null && $exactDays === null) {
            return null;
        }

        // Conflicting values are resolved conservatively: actual start cannot be
        // earlier than any stated availability, exact start date, or notice period.
        return max($availabilityDays ?? 0, $noticeDays ?? 0, $exactDays ?? 0);
    }

    private function daysFromAvailabilityText(string $text, bool $noticeField): ?int
    {
        $text = Str::lower(trim($text));
        if ($text === '') {
            return null;
        }
        if (Str::contains($text, ['immediately', 'can start immediately', 'none / can start immediately'])) {
            return 0;
        }
        if ($noticeField && Str::contains($text, 'not applicable')) {
            return 0;
        }
        if (Str::contains($text, 'more than 30 days')) {
            return 31;
        }
        if (preg_match('/(?:within\s+)?(\d+)\s*weeks?/i', $text, $m)) {
            return (int) $m[1] * 7;
        }
        if (preg_match('/(?:within\s+)?(\d+)\s*days?/i', $text, $m)) {
            return (int) $m[1];
        }
        if (Str::contains($text, 'to be discussed')) {
            return null;
        }
        return null;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        $value = trim($value);
        // HTML date fields persist ISO dates. Reject rollover dates and relative prose.
        if (!preg_match('/^(\d{4})-(\d{2})(?:-(\d{2}))?$/D', $value, $parts)) {
            return null;
        }
        $day = (int) ($parts[3] ?? 1);
        if (!checkdate((int) $parts[2], $day, (int) $parts[1])) {
            return null;
        }
        try {
            return Carbon::createMidnightDate((int) $parts[1], (int) $parts[2], $day);
        } catch (\Throwable) {
            return null;
        }
    }

    private function monthsLabel(int $months): string
    {
        if ($months <= 0) {
            return '0 months';
        }
        $years = intdiv($months, 12);
        $remaining = $months % 12;
        if ($years && $remaining) {
            return $years . ' yr ' . $remaining . ' mo';
        }
        if ($years) {
            return $years . ($years === 1 ? ' yr' : ' yrs');
        }
        return $remaining . ' mo';
    }

    private function excludedField(?FormField $field): bool
    {
        if (!$field) {
            return true;
        }

        $key = Str::lower(trim((string) ($field->field_key ?? '')));
        if ($key !== '') {
            if (in_array($key, $this->excludedFieldKeys, true)) {
                return true;
            }

            // Professional references are collected for later HR verification but
            // are not candidate-fit evidence. Keep company_*/company_address_* intact.
            if (Str::startsWith($key, 'reference_')) {
                return true;
            }

            return false;
        }

        // Fallback only for malformed legacy rows that truly have no field_key.
        // Exact/phrase checks avoid the previous false-positive on "Company Name"
        // and "Company Address".
        $label = Str::lower(trim((string) ($field->label ?? '')));
        return (bool) preg_match(
            '/^(?:first|middle|last)\s+name$|^nickname$|^(?:present|permanent|home)\s+address$|^birth(?:date|day)$|^age$|^gender$|^sex$|^civil\s+status$|^marital\s+status$|^religion$|^blood\s+type$|^(?:cellphone|phone|mobile)(?:\s+number)?$|^email(?:\s+address)?$|^facebook(?:\s+account)?$/i',
            $label
        );
    }

    private function stringValue($value): string
    {
        if (is_array($value)) {
            return trim(implode(' ', array_map('strval', $value)));
        }
        $decoded = json_decode((string) $value, true);
        return is_array($decoded)
            ? trim(implode(' ', array_map('strval', $decoded)))
            : trim(strip_tags((string) $value));
    }

    private function terms(string $string): array
    {
        $stop = [
            'the','and','for','with','that','this','from','your','you','are','our','will','have','has','job','work','role','must',
            'can','able','into','about','position','candidate','applicant','required','preferred','related','relevant','good','strong',
        ];
        $words = preg_split('/[^a-z0-9+#.]+/i', Str::lower(strip_tags($string))) ?: [];
        return array_values(array_unique(array_filter(
            $words,
            fn ($word) => strlen($word) >= 3 && !in_array($word, $stop, true)
        )));
    }

    private function fit(float $score, int $confidence, ?array $qualificationAssessment): string
    {
        $qualificationCoverage = (int) ($qualificationAssessment['coverage'] ?? 0);
        $requiredNotMatched = (int) ($qualificationAssessment['required_not_matched_count'] ?? 0);
        $requiredNotVerified = (int) ($qualificationAssessment['required_not_verified_count'] ?? 0);
        $criticalNotVerified = (int) ($qualificationAssessment['critical_not_verified_count'] ?? 0);

        if ($qualificationAssessment === null || ($qualificationAssessment['score'] ?? null) === null) {
            return 'Insufficient Evidence';
        }
        if ($confidence < 55 || $qualificationCoverage < 45) {
            return 'Insufficient Evidence';
        }
        if ($requiredNotMatched > 0) {
            return 'Needs Review';
        }
        if ($criticalNotVerified > 0) {
            return 'Insufficient Evidence';
        }
        if ($requiredNotVerified > 0) {
            return $qualificationCoverage < 70 ? 'Insufficient Evidence' : 'Needs Review';
        }
        if ($score >= 85 && $confidence >= 85) {
            return 'Strong Fit';
        }
        if ($score >= 75 && $confidence >= 70) {
            return 'Good Fit';
        }
        if ($score >= 60 && $confidence >= 60) {
            return 'Moderate Fit';
        }

        return 'Needs Review';
    }
}
