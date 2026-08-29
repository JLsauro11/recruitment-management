<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FormTemplateController extends Controller
{
    public function index(): View
    {
        return view('recruitment.forms.index');
    }

    public function data(): JsonResponse
    {
        $templates = FormTemplate::withCount(['fields', 'submissions'])
            ->orderBy('sort_order')->orderBy('name')->get()->map(fn ($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'type' => $template->type,
                'description' => $template->description,
                'fields_count' => $template->fields_count,
                'submissions_count' => $template->submissions_count,
                'is_active' => $template->is_active,
            ]);

        return response()->json(['data' => $templates]);
    }

    public function sections(): JsonResponse
    {
        $sections = \App\Models\FormField::query()
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->values();

        return response()->json(['data' => $sections]);
    }

    public function show(FormTemplate $formTemplate): JsonResponse
    {
        $formTemplate->load('fields');

        return response()->json([
            ...$formTemplate->toArray(),
            'sections' => $formTemplate->fields
                ->pluck('section')
                ->filter()
                ->unique()
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $template = FormTemplate::create(collect($data)->except('fields')->all());
            $this->syncFields($template, $data['fields']);
        });

        return response()->json(['message' => 'Form template created successfully.']);
    }

    public function update(Request $request, FormTemplate $formTemplate): JsonResponse
    {
        $data = $this->validated($request, $formTemplate);

        DB::transaction(function () use ($data, $formTemplate) {
            $formTemplate->update(collect($data)->except('fields')->all());
            $this->syncFields($formTemplate, $data['fields'], true);
        });

        return response()->json(['message' => 'Form template updated successfully.']);
    }

    public function destroy(FormTemplate $formTemplate): JsonResponse
    {
        if ($formTemplate->submissions()->exists()) {
            return response()->json([
                'message' => 'This template already has submissions. Deactivate it instead of deleting it.'
            ], 422);
        }

        $formTemplate->delete();
        return response()->json(['message' => 'Form template deleted successfully.']);
    }

    private function validated(Request $request, ?FormTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['application', 'questionnaire'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.section' => ['nullable', 'string', 'max:150'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.field_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', 'distinct'],
            'fields.*.field_type' => ['required', Rule::in(['text','email','number','date','select','textarea','radio','checkbox','file'])],
            'fields.*.options' => ['nullable'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'fields.*.width' => ['nullable', 'integer', 'min:1', 'max:12'],
        ], [
            'fields.*.field_key.distinct' => 'Each field key must be unique within the template.',
            'fields.*.field_key.regex' => 'Field keys may only contain lowercase letters, numbers, and underscores.',
        ]);
    }

    private function syncFields(FormTemplate $template, array $fields, bool $removeMissing = false): void
    {
        $receivedKeys = collect($fields)->pluck('field_key')->filter()->values();

        foreach (array_values($fields) as $index => $field) {
            $options = $field['options'] ?? null;
            if (is_string($options)) {
                $options = collect(explode(',', $options))->map(fn ($v) => trim($v))->filter()->values()->all();
            }

            $template->fields()->updateOrCreate(
                ['field_key' => $field['field_key']],
                [
                'section' => $field['section'] ?? null,
                'label' => $field['label'],
                'field_key' => $field['field_key'],
                'field_type' => $field['field_type'],
                'options' => $options ?: null,
                'placeholder' => $field['placeholder'] ?? null,
                'is_required' => (bool) ($field['is_required'] ?? false),
                'sort_order' => $field['sort_order'] ?? $index,
                'width' => $field['width'] ?? 12,
                ]
            );
        }

        if ($removeMissing) {
            $template->fields()->whereNotIn('field_key', $receivedKeys)->get()->each(function ($field) {
                if (!$field->answers()->exists()) {
                    $field->delete();
                }
            });
        }
    }
}
