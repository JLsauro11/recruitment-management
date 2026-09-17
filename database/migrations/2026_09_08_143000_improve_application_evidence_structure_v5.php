<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $application = DB::table('form_templates')
            ->where('type', 'application')
            ->where('name', 'Application for Employment')
            ->first()
            ?? DB::table('form_templates')->where('type', 'application')->orderBy('sort_order')->first();

        if ($application) {
            DB::table('form_templates')->where('id', $application->id)->update([
                'name' => 'Application for Employment',
                'type' => 'application',
                'description' => 'Official RedSpeed Motoworkz OPC employment application with structured education, credential, employment, tool, and readiness evidence for automated assessment.',
                'is_active' => true,
                'updated_at' => $now,
            ]);

            // Job Application Data: exact start date and work-arrangement readiness
            // reduce ambiguity when a vacancy contains availability/shift/onsite rules.
            $this->upsertField($application->id, 'availability', [
                'section' => 'Job Application Data',
                'label' => 'Availability to Start',
                'field_type' => 'select',
                'options' => ['Immediately','Within 1 Week','Within 2 Weeks','Within 30 Days','More than 30 Days','Specific Date','To be discussed'],
                'placeholder' => null,
                'is_required' => true,
                'sort_order' => 1,
                'width' => 4,
            ], $now);
            $this->upsertField($application->id, 'available_start_date', [
                'section' => 'Job Application Data',
                'label' => 'Specific Available Start Date',
                'field_type' => 'date',
                'options' => null,
                'placeholder' => null,
                'is_required' => false,
                'sort_order' => 2,
                'width' => 4,
            ], $now);
            $this->reorderExisting($application->id, 'current_employment_status', 3, $now);
            $this->reorderExisting($application->id, 'notice_period', 4, $now);
            $this->upsertField($application->id, 'schedule_readiness', [
                'section' => 'Job Application Data',
                'label' => 'Willing to Work the Required Schedule / Shift?',
                'field_type' => 'select',
                'options' => ['Yes','No','To be discussed'],
                'placeholder' => null,
                'is_required' => true,
                'sort_order' => 5,
                'width' => 4,
            ], $now);
            $this->upsertField($application->id, 'onsite_readiness', [
                'section' => 'Job Application Data',
                'label' => 'Willing to Work On-site if Required?',
                'field_type' => 'select',
                'options' => ['Yes','No','To be discussed'],
                'placeholder' => null,
                'is_required' => true,
                'sort_order' => 6,
                'width' => 4,
            ], $now);
            $this->reorderExisting($application->id, 'applied_through', 7, $now);
            $this->reorderExisting($application->id, 'referred_by', 8, $now);

            // Personal data stays administrative only. Age is derived from birthdate,
            // and blood type becomes a constrained value instead of arbitrary text.
            $this->updateExisting($application->id, 'age', [
                'label' => 'Age',
                'field_type' => 'number',
                'placeholder' => 'Auto-calculated from birthdate',
                'width' => 2,
            ], $now);
            $this->updateExisting($application->id, 'blood_type', [
                'label' => 'Blood Type',
                'field_type' => 'select',
                'options' => ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown / Not Sure','Prefer not to say'],
                'placeholder' => null,
                'width' => 2,
            ], $now);

            // Education completion is explicit so a degree title is not mistaken for
            // proof that the degree was actually completed.
            $this->upsertField($application->id, 'college_completion_status', [
                'section' => 'Educational Background',
                'label' => 'College / Degree Completion Status',
                'field_type' => 'select',
                'options' => ['Graduated / Degree Completed','Undergraduate / Not Completed','Currently Enrolled','No College / Not Applicable'],
                'placeholder' => null,
                'is_required' => true,
                'sort_order' => 90,
                'width' => 6,
            ], $now);
            $this->upsertField($application->id, 'college_graduation_year', [
                'section' => 'Educational Background',
                'label' => 'Graduation / Expected Graduation Year',
                'field_type' => 'number',
                'options' => null,
                'placeholder' => 'Example: 2026',
                'is_required' => false,
                'sort_order' => 91,
                'width' => 6,
            ], $now);

            $this->upsertField($application->id, 'professional_credential_declaration', [
                'section' => 'Educational Background',
                'label' => 'Do you have any professional license, board/licensure qualification, TESDA/National Certificate, or professional accreditation?',
                'field_type' => 'select',
                'options' => ['Yes','No'],
                'placeholder' => null,
                'is_required' => true,
                'sort_order' => 99,
                'width' => 12,
            ], $now);

            // Reuse the two legacy textarea keys as structured record #1 names so
            // historical answers remain readable. New supporting fields make the
            // credential assessable without free-form interpretation.
            $this->upsertField($application->id, 'professional_qualifications', [
                'section' => 'Educational Background',
                'label' => 'Professional Qualification / License Name 1',
                'field_type' => 'text',
                'options' => null,
                'placeholder' => 'Example: Certified Public Accountant (CPA)',
                'is_required' => false,
                'sort_order' => 100,
                'width' => 4,
            ], $now);
            $this->credentialFields($application->id, 'professional_qualification', 1, 101, $now);
            $this->upsertField($application->id, 'professional_qualification_name_2', [
                'section' => 'Educational Background',
                'label' => 'Professional Qualification / License Name 2',
                'field_type' => 'text',
                'options' => null,
                'placeholder' => 'Optional second qualification or license',
                'is_required' => false,
                'sort_order' => 110,
                'width' => 4,
            ], $now);
            $this->credentialFields($application->id, 'professional_qualification', 2, 111, $now);

            $this->upsertField($application->id, 'certification_training_declaration', [
                'section' => 'Educational Background',
                'label' => 'Do you have any relevant certification, training, seminar/workshop, or completed technical course to declare?',
                'field_type' => 'select',
                'options' => ['Yes','No'],
                'placeholder' => null,
                'is_required' => true,
                'sort_order' => 119,
                'width' => 12,
            ], $now);

            $this->upsertField($application->id, 'relevant_certifications', [
                'section' => 'Educational Background',
                'label' => 'Certification / Training Name 1',
                'field_type' => 'text',
                'options' => null,
                'placeholder' => 'Example: QuickBooks Online Certification',
                'is_required' => false,
                'sort_order' => 120,
                'width' => 4,
            ], $now);
            $this->trainingFields($application->id, 1, 121, $now);
            $this->upsertField($application->id, 'certification_name_2', [
                'section' => 'Educational Background',
                'label' => 'Certification / Training Name 2',
                'field_type' => 'text',
                'options' => null,
                'placeholder' => 'Optional second certification or training',
                'is_required' => false,
                'sort_order' => 130,
                'width' => 4,
            ], $now);
            $this->trainingFields($application->id, 2, 131, $now);

            // Employment evidence is separated into responsibilities, named tools,
            // and achievements/results. This is substantially more reliable than a
            // single mixed textarea for both role relevance and performance evidence.
            $this->upsertField($application->id, 'work_experience_declaration', [
                'section' => 'Employment History',
                'label' => 'Do you have previous or current work experience?',
                'field_type' => 'select',
                'options' => ['Yes','No'],
                'placeholder' => null,
                'is_required' => true,
                'sort_order' => 199,
                'width' => 12,
            ], $now);
            foreach ([1,2] as $record) {
                $base = $record === 1 ? 200 : 220;
                $keys = [
                    ["company_{$record}", "Company Name {$record}", 'text', 4, $base],
                    ["company_address_{$record}", "Company Address {$record}", 'text', 4, $base + 1],
                    ["position_held_{$record}", "Position Held {$record}", 'text', 4, $base + 2],
                    ["employment_dates_{$record}", "Employment Start Date {$record}", 'date', 3, $base + 3],
                    ["employment_end_{$record}", "Employment End Date {$record}", 'date', 3, $base + 4],
                    ["currently_employed_{$record}", 'Currently Employed Here?', 'select', 3, $base + 5],
                    ["reason_leaving_{$record}", "Reason for Leaving {$record}", 'text', 3, $base + 6],
                    ["duties_{$record}", "Primary Responsibilities {$record}", 'textarea', 12, $base + 7],
                ];
                foreach ($keys as [$key,$label,$type,$width,$order]) {
                    $payload = ['section'=>'Employment History','label'=>$label,'field_type'=>$type,'placeholder'=>null,'is_required'=>false,'sort_order'=>$order,'width'=>$width];
                    if ($key === "currently_employed_{$record}") {
                        $payload['options'] = ['Yes','No'];
                    } else {
                        $payload['options'] = null;
                    }
                    $this->upsertField($application->id, $key, $payload, $now);
                }
                $this->upsertField($application->id, "employment_tools_{$record}", [
                    'section' => 'Employment History',
                    'label' => "Tools / Software / Systems Used {$record}",
                    'field_type' => 'text',
                    'options' => null,
                    'placeholder' => 'Example: NetSuite, QuickBooks, Excel',
                    'is_required' => false,
                    'sort_order' => $base + 8,
                    'width' => 12,
                ], $now);
                $this->upsertField($application->id, "employment_achievements_{$record}", [
                    'section' => 'Employment History',
                    'label' => "Key Achievements / Results {$record}",
                    'field_type' => 'textarea',
                    'options' => null,
                    'placeholder' => 'Give specific results, improvements, volume handled, accuracy, savings, time saved, targets, or other measurable outcomes when possible.',
                    'is_required' => false,
                    'sort_order' => $base + 9,
                    'width' => 12,
                ], $now);
            }
        }

        $questionnaire = DB::table('form_templates')
            ->where('type', 'questionnaire')
            ->where('name', 'Employment Questionnaire')
            ->first()
            ?? DB::table('form_templates')->where('type', 'questionnaire')->orderBy('sort_order')->first();

        if ($questionnaire) {
            DB::table('form_templates')->where('id', $questionnaire->id)->update([
                'name' => 'Employment Questionnaire',
                'type' => 'questionnaire',
                'description' => 'Fixed employment questionnaire. Culture-preference answers remain informational; structured role evidence supports automated assessment.',
                'is_active' => true,
                'updated_at' => $now,
            ]);

            $this->updateExisting($questionnaire->id, 'role_relevant_skills', [
                'placeholder' => 'List only skills you can support with actual work, project, or training evidence. Be specific.',
                'sort_order' => 7,
            ], $now);
            $this->upsertField($questionnaire->id, 'role_tools_systems', [
                'section' => 'Role-Specific Assessment',
                'label' => 'List the exact tools, software, systems, equipment, or platforms you have actually used that are relevant to this position.',
                'field_type' => 'text',
                'options' => null,
                'placeholder' => 'Example: NetSuite, QuickBooks, Microsoft Excel',
                'is_required' => false,
                'sort_order' => 8,
                'width' => 12,
            ], $now);
            $this->reorderExisting($questionnaire->id, 'role_similar_project', 9, $now);
            $this->upsertField($questionnaire->id, 'role_problem_solving', [
                'section' => 'Role-Specific Assessment',
                'label' => 'Problem / Situation: Describe a difficult work-related problem you personally faced.',
                'field_type' => 'textarea',
                'options' => null,
                'placeholder' => 'Describe the context and what made the problem difficult.',
                'is_required' => true,
                'sort_order' => 10,
                'width' => 12,
            ], $now);
            $this->upsertField($questionnaire->id, 'role_problem_action', [
                'section' => 'Role-Specific Assessment',
                'label' => 'Action: What exactly did you personally do to address the problem?',
                'field_type' => 'textarea',
                'options' => null,
                'placeholder' => 'State your own actions, decisions, checks, analysis, coordination, or solution steps.',
                'is_required' => true,
                'sort_order' => 11,
                'width' => 12,
            ], $now);
            $this->upsertField($questionnaire->id, 'role_problem_result', [
                'section' => 'Role-Specific Assessment',
                'label' => 'Result: What happened after your action? Give a concrete outcome when possible.',
                'field_type' => 'textarea',
                'options' => null,
                'placeholder' => 'State the outcome. Include measurable improvement, accuracy, time, cost, volume, or other result when available.',
                'is_required' => true,
                'sort_order' => 12,
                'width' => 12,
            ], $now);
            $this->reorderExisting($questionnaire->id, 'role_strongest_requirement', 13, $now);
            $this->reorderExisting($questionnaire->id, 'role_training_gap', 14, $now);
        }
    }

    public function down(): void
    {
        // Non-destructive by design. Historical application answers reference these
        // field ids, so rollback must not delete structured evidence fields.
    }

    private function credentialFields(int $templateId, string $prefix, int $record, int $order, $now): void
    {
        $this->upsertField($templateId, "{$prefix}_type_{$record}", [
            'section' => 'Educational Background',
            'label' => "Qualification Type {$record}",
            'field_type' => 'select',
            'options' => ['Professional License','Board / Licensure Exam','TESDA / National Certificate','Professional Accreditation','Other'],
            'placeholder' => null,
            'is_required' => false,
            'sort_order' => $order,
            'width' => 4,
        ], $now);
        $this->upsertField($templateId, "{$prefix}_issuer_{$record}", [
            'section' => 'Educational Background',
            'label' => "Issuing Organization {$record}",
            'field_type' => 'text',
            'options' => null,
            'placeholder' => 'Example: PRC, TESDA, professional body',
            'is_required' => false,
            'sort_order' => $order + 1,
            'width' => 4,
        ], $now);
        $this->upsertField($templateId, "{$prefix}_status_{$record}", [
            'section' => 'Educational Background',
            'label' => "Credential Status {$record}",
            'field_type' => 'select',
            'options' => ['Active / Valid','Completed / Passed','Expired','Pending / In Process'],
            'placeholder' => null,
            'is_required' => false,
            'sort_order' => $order + 2,
            'width' => 4,
        ], $now);
        $this->upsertField($templateId, "{$prefix}_valid_until_{$record}", [
            'section' => 'Educational Background',
            'label' => "Valid Until {$record}",
            'field_type' => 'date',
            'options' => null,
            'placeholder' => null,
            'is_required' => false,
            'sort_order' => $order + 3,
            'width' => 6,
        ], $now);
        $this->upsertField($templateId, "{$prefix}_credential_{$record}", [
            'section' => 'Educational Background',
            'label' => "Credential / License Number {$record}",
            'field_type' => 'text',
            'options' => null,
            'placeholder' => 'Optional — for HR verification',
            'is_required' => false,
            'sort_order' => $order + 4,
            'width' => 6,
        ], $now);
    }

    private function trainingFields(int $templateId, int $record, int $order, $now): void
    {
        $this->upsertField($templateId, "certification_type_{$record}", [
            'section' => 'Educational Background',
            'label' => "Certification / Training Type {$record}",
            'field_type' => 'select',
            'options' => ['Certification','Training','Seminar / Workshop','TESDA / National Certificate','Other'],
            'placeholder' => null,
            'is_required' => false,
            'sort_order' => $order,
            'width' => 4,
        ], $now);
        $this->upsertField($templateId, "certification_provider_{$record}", [
            'section' => 'Educational Background',
            'label' => "Provider / Issuer {$record}",
            'field_type' => 'text',
            'options' => null,
            'placeholder' => 'Organization or training provider',
            'is_required' => false,
            'sort_order' => $order + 1,
            'width' => 4,
        ], $now);
        $this->upsertField($templateId, "certification_status_{$record}", [
            'section' => 'Educational Background',
            'label' => "Completion / Validity Status {$record}",
            'field_type' => 'select',
            'options' => ['Completed / Valid','In Progress','Expired','Pending Verification'],
            'placeholder' => null,
            'is_required' => false,
            'sort_order' => $order + 2,
            'width' => 4,
        ], $now);
        $this->upsertField($templateId, "certification_completion_date_{$record}", [
            'section' => 'Educational Background',
            'label' => "Completion Date {$record}",
            'field_type' => 'date',
            'options' => null,
            'placeholder' => null,
            'is_required' => false,
            'sort_order' => $order + 3,
            'width' => 6,
        ], $now);
        $this->upsertField($templateId, "certification_credential_{$record}", [
            'section' => 'Educational Background',
            'label' => "Certificate / Credential Number {$record}",
            'field_type' => 'text',
            'options' => null,
            'placeholder' => 'Optional — for HR verification',
            'is_required' => false,
            'sort_order' => $order + 4,
            'width' => 6,
        ], $now);
    }

    private function reorderExisting(int $templateId, string $key, int $order, $now): void
    {
        DB::table('form_fields')
            ->where('form_template_id', $templateId)
            ->where('field_key', $key)
            ->update(['sort_order' => $order, 'updated_at' => $now]);
    }

    private function updateExisting(int $templateId, string $key, array $data, $now): void
    {
        if (array_key_exists('options', $data) && is_array($data['options'])) {
            $data['options'] = json_encode($data['options']);
        }
        $data['updated_at'] = $now;
        DB::table('form_fields')
            ->where('form_template_id', $templateId)
            ->where('field_key', $key)
            ->update($data);
    }

    private function upsertField(int $templateId, string $key, array $data, $now): void
    {
        $payload = array_merge($data, [
            'options' => array_key_exists('options', $data) && is_array($data['options'])
                ? json_encode($data['options'])
                : ($data['options'] ?? null),
            'updated_at' => $now,
        ]);

        $existing = DB::table('form_fields')
            ->where('form_template_id', $templateId)
            ->where('field_key', $key)
            ->first();

        if ($existing) {
            DB::table('form_fields')->where('id', $existing->id)->update($payload);
            return;
        }

        DB::table('form_fields')->insert(array_merge($payload, [
            'form_template_id' => $templateId,
            'field_key' => $key,
            'created_at' => $now,
        ]));
    }
};
