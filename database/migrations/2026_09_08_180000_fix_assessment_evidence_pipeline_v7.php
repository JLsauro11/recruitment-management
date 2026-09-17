<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $template = DB::table('form_templates')
            ->where('type', 'questionnaire')
            ->where('name', 'Employment Questionnaire')
            ->first()
            ?? DB::table('form_templates')->where('type', 'questionnaire')->orderBy('sort_order')->first();

        if (! $template) {
            return;
        }

        $now = now();

        // Forward-fix for installations that already ran the earlier V5 migration
        // before role_problem_action / role_problem_result were added to that file.
        // Laravel never reruns an already-recorded migration, so these fields must be
        // restored by a new migration to ensure actual submitted Action/Result evidence
        // reaches Assessment Insights on existing databases.
        $fields = [
            [
                'key' => 'role_problem_solving',
                'label' => 'Problem / Situation: Describe a difficult work-related problem you personally faced.',
                'placeholder' => 'Describe the context and what made the problem difficult.',
                'order' => 10,
            ],
            [
                'key' => 'role_problem_action',
                'label' => 'Action: What exactly did you personally do to address the problem?',
                'placeholder' => 'State your own actions, decisions, checks, analysis, coordination, or solution steps.',
                'order' => 11,
            ],
            [
                'key' => 'role_problem_result',
                'label' => 'Result: What happened after your action? Give a concrete outcome when possible.',
                'placeholder' => 'State the outcome. Include measurable improvement, accuracy, time, cost, volume, or another concrete result when available.',
                'order' => 12,
            ],
        ];

        foreach ($fields as $field) {
            $payload = [
                'section' => 'Role-Specific Assessment',
                'label' => $field['label'],
                'field_type' => 'textarea',
                'options' => null,
                'placeholder' => $field['placeholder'],
                'is_required' => true,
                'sort_order' => $field['order'],
                'width' => 12,
                'updated_at' => $now,
            ];

            $existing = DB::table('form_fields')
                ->where('form_template_id', $template->id)
                ->where('field_key', $field['key'])
                ->first();

            if ($existing) {
                DB::table('form_fields')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('form_fields')->insert(array_merge($payload, [
                    'form_template_id' => $template->id,
                    'field_key' => $field['key'],
                    'created_at' => $now,
                ]));
            }
        }

        // Keep the remaining role-assessment fields in a deterministic order so the
        // fixed questionnaire is stable after upgrades from older project versions.
        $orders = [
            'role_relevant_skills' => 7,
            'role_tools_systems' => 8,
            'role_similar_project' => 9,
            'role_strongest_requirement' => 13,
            'role_training_gap' => 14,
        ];
        foreach ($orders as $key => $order) {
            DB::table('form_fields')
                ->where('form_template_id', $template->id)
                ->where('field_key', $key)
                ->update(['sort_order' => $order, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        // Non-destructive. Existing submissions may already reference these fields.
    }
};
