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
                'description' => 'Official RedSpeed Motoworkz OPC concise employment application. Detailed role evidence is collected once in the Employment Questionnaire for more reliable automated assessment.',
                'is_active' => true,
                'sort_order' => 1,
                'updated_at' => $now,
            ]);

            $fields = $this->applicationFields();
            foreach ($fields as $index => $field) {
                $this->upsertField($application->id, $field['key'], [
                    'section' => $field['section'],
                    'label' => $field['label'],
                    'field_type' => $field['type'],
                    'options' => $field['options'] ?? null,
                    'placeholder' => $field['placeholder'] ?? null,
                    'is_required' => $field['required'],
                    'sort_order' => $index + 1,
                    'width' => $field['width'],
                ], $now);
            }

            // Keep legacy V5 field rows so old submitted answers remain readable,
            // but move them out of the active workflow and never require them again.
            $activeKeys = array_column($fields, 'key');
            DB::table('form_fields')
                ->where('form_template_id', $application->id)
                ->whereNotIn('field_key', $activeKeys)
                ->update([
                    'is_required' => false,
                    'sort_order' => 9000,
                    'updated_at' => $now,
                ]);
        }

        $questionnaire = DB::table('form_templates')
            ->where('type', 'questionnaire')
            ->where('name', 'Employment Questionnaire')
            ->first()
            ?? DB::table('form_templates')->where('type', 'questionnaire')->orderBy('sort_order')->first();

        if ($questionnaire) {
            DB::table('form_templates')->where('id', $questionnaire->id)->update([
                'name' => 'Employment Questionnaire',
                'description' => 'Fixed questionnaire. Detailed skills, actual tools/platforms, comparable work, and Situation-Action-Result evidence are collected here once so Assessment Insights does not double-count repeated employment text.',
                'is_active' => true,
                'sort_order' => 2,
                'updated_at' => $now,
            ]);

            $this->upsertField($questionnaire->id, 'role_tools_systems', [
                'section' => 'Role-Specific Assessment',
                'label' => 'List the exact tools, software, systems, equipment, or platforms you have actually used that are relevant to this position. If none, type None.',
                'field_type' => 'text',
                'options' => null,
                'placeholder' => 'Example: Windows Server, Active Directory, Microsoft 365, DNS, DHCP. Type None if not applicable.',
                'is_required' => true,
                'sort_order' => 8,
                'width' => 12,
            ], $now);
        }
    }

    public function down(): void
    {
        // Non-destructive by design. Historical submissions may reference legacy
        // field ids, so rollback never deletes or rewrites submitted answers.
    }

    private function applicationFields(): array
    {
        $rows = [
            // Job Application Data: only basic readiness/source details.
            $this->f('Job Application Data', 'Availability to Start', 'availability', 'select', true, 4, ['Immediately','Within 1 Week','Within 2 Weeks','Within 30 Days','More than 30 Days','To be discussed']),
            $this->f('Job Application Data', 'Current Employment Status', 'current_employment_status', 'select', true, 4, ['Employed','Self-Employed','Unemployed','Student','Freelance / Project-Based']),
            $this->f('Job Application Data', 'Notice Period', 'notice_period', 'select', false, 4, ['None / Can start immediately','1 Week','2 Weeks','30 Days','More than 30 Days','Not Applicable']),
            $this->f('Job Application Data', 'Applied Through', 'applied_through', 'select', true, 6, ['Walk In','Email','Facebook Page','Website','Job Fair','Employee Referral']),
            $this->f('Job Application Data', 'Referred By', 'referred_by', 'text', false, 6, null, 'Name of referrer, if applicable'),

            // One address only; no Facebook account.
            $this->f('Personal Information', 'Last Name', 'last_name', 'text', true, 3),
            $this->f('Personal Information', 'First Name', 'first_name', 'text', true, 3),
            $this->f('Personal Information', 'Middle Name', 'middle_name', 'text', false, 3),
            $this->f('Personal Information', 'Nickname', 'nickname', 'text', false, 3),
            $this->f('Personal Information', 'Address', 'present_address', 'textarea', true, 12),
            $this->f('Personal Information', 'Birthdate', 'birthdate', 'date', true, 3),
            $this->f('Personal Information', 'Age', 'age', 'number', false, 2, null, 'Auto-calculated from birthdate'),
            $this->f('Personal Information', 'Gender', 'gender', 'select', true, 2, ['Male','Female','Prefer not to say']),
            $this->f('Personal Information', 'Civil Status', 'civil_status', 'select', true, 2, ['Single','Married','Widowed','Separated','With Partner']),
            $this->f('Personal Information', 'Religion', 'religion', 'text', false, 2),
            $this->f('Personal Information', 'Blood Type', 'blood_type', 'select', false, 3, ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown / Not Sure','Prefer not to say']),
            $this->f('Personal Information', 'Cellphone Number', 'cellphone_number', 'text', true, 4),
            $this->f('Personal Information', 'Email Address', 'email_address', 'email', true, 5),

            // Education only. Degree/Course stays attached to College/University so
            // a vacancy education requirement still has structured evidence.
            $this->f('Educational Background', 'Elementary School', 'elementary_school', 'text', false, 4),
            $this->f('Educational Background', 'Elementary Address', 'elementary_address', 'text', false, 4),
            $this->f('Educational Background', 'Elementary Inclusive Dates', 'elementary_dates', 'text', false, 4),
            $this->f('Educational Background', 'High School', 'highschool_school', 'text', false, 4),
            $this->f('Educational Background', 'High School Address', 'highschool_address', 'text', false, 4),
            $this->f('Educational Background', 'High School Inclusive Dates', 'highschool_dates', 'text', false, 4),
            $this->f('Educational Background', 'Vocational School / Course', 'vocational_school', 'text', false, 4),
            $this->f('Educational Background', 'Vocational Address', 'vocational_address', 'text', false, 4),
            $this->f('Educational Background', 'Vocational Inclusive Dates', 'vocational_dates', 'text', false, 4),
            $this->f('Educational Background', 'College / University', 'college_school', 'text', false, 3),
            $this->f('Educational Background', 'College Address', 'college_address', 'text', false, 3),
            $this->f('Educational Background', 'Degree / Course', 'college_course', 'text', false, 3, null, 'Example: Bachelor of Science in Information Technology'),
            $this->f('Educational Background', 'College Inclusive Dates', 'college_dates', 'text', false, 3),

            $this->f('Employment History', 'Do you have previous or current work experience?', 'work_experience_declaration', 'select', true, 12, ['Yes','No']),
        ];

        for ($record = 1; $record <= 5; $record++) {
            $rows[] = $this->f('Employment History', "Company Name {$record}", "company_{$record}", 'text', false, 4);
            $rows[] = $this->f('Employment History', "Company Address {$record}", "company_address_{$record}", 'text', false, 4);
            $rows[] = $this->f('Employment History', "Position Held {$record}", "position_held_{$record}", 'text', false, 4);
            $rows[] = $this->f('Employment History', "Employment Start Date {$record}", "employment_dates_{$record}", 'date', false, 4);
            $rows[] = $this->f('Employment History', "Employment End Date {$record}", "employment_end_{$record}", 'date', false, 4);
            $rows[] = $this->f('Employment History', "Currently Employed Here? {$record}", "currently_employed_{$record}", 'select', false, 4, ['Yes','No']);
        }

        return array_merge($rows, [
            $this->f('References', 'Reference Name 1', 'reference_name_1', 'text', false, 3),
            $this->f('References', 'Position / Title 1', 'reference_title_1', 'text', false, 3),
            $this->f('References', 'Company 1', 'reference_company_1', 'text', false, 3),
            $this->f('References', 'Contact Number 1', 'reference_phone_1', 'text', false, 3),
            $this->f('References', 'Reference Name 2', 'reference_name_2', 'text', false, 3),
            $this->f('References', 'Position / Title 2', 'reference_title_2', 'text', false, 3),
            $this->f('References', 'Company 2', 'reference_company_2', 'text', false, 3),
            $this->f('References', 'Contact Number 2', 'reference_phone_2', 'text', false, 3),
            $this->f('Documents', 'Resume / CV', 'resume', 'file', false, 12),
        ]);
    }

    private function f(string $section, string $label, string $key, string $type, bool $required, int $width, ?array $options = null, ?string $placeholder = null): array
    {
        return compact('section', 'label', 'key', 'type', 'required', 'width', 'options', 'placeholder');
    }

    private function upsertField(int $templateId, string $key, array $payload, $now): void
    {
        $payload['options'] = isset($payload['options']) && is_array($payload['options'])
            ? json_encode(array_values($payload['options']))
            : null;
        $payload['updated_at'] = $now;

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
