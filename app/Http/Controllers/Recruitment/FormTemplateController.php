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
        // Keep legacy/duplicate values clean so every template always has one unique order.
        $this->normalizeTemplateOrders();

        $templates = FormTemplate::withCount(['fields', 'submissions'])
            ->orderBy('sort_order')->orderBy('id')->get()->map(fn ($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'type' => $template->type,
                'description' => $template->description,
                'fields_count' => $template->fields_count,
                'submissions_count' => $template->submissions_count,
                'is_active' => $template->is_active,
                'sort_order' => (int) $template->sort_order,
                'is_system_managed' => $this->isSystemManagedTemplate($template),
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

        if ($this->isReservedTemplateIdentity((string) $data['name'], (string) $data['type'])) {
            return response()->json([
                'message' => 'That template name is reserved for the system-managed Assessment Insights form pair.',
            ], 422);
        }

        DB::transaction(function () use ($data) {
            $this->normalizeTemplateOrders();

            $nextOrder = ((int) FormTemplate::max('sort_order')) + 1;
            $requestedOrder = max(1, (int) ($data['sort_order'] ?? $nextOrder));
            $requestedOrder = min($requestedOrder, max(1, $nextOrder));

            // A new template starts at the end. If the requested position is occupied,
            // swap the existing template to that end position instead of creating duplicates.
            $occupied = FormTemplate::where('sort_order', $requestedOrder)->first();
            if ($occupied) {
                $occupied->update(['sort_order' => $nextOrder]);
            }

            $templateData = collect($data)->except('fields')->all();
            $templateData['sort_order'] = $requestedOrder;
            $template = FormTemplate::create($templateData);
            $this->syncFields($template, $data['fields']);
        });

        return response()->json(['message' => 'Form template created successfully.']);
    }

    public function update(Request $request, FormTemplate $formTemplate): JsonResponse
    {
        if ($this->isSystemManagedTemplate($formTemplate)) {
            return response()->json([
                'message' => 'This is a system-managed employment form used by Assessment Insights. Its structure is fixed so HR does not need to configure assessment mappings manually.',
            ], 422);
        }

        $data = $this->validated($request, $formTemplate);

        if ($this->isReservedTemplateIdentity((string) $data['name'], (string) $data['type'])) {
            return response()->json([
                'message' => 'That template name is reserved for the system-managed Assessment Insights form pair.',
            ], 422);
        }

        DB::transaction(function () use ($data, $formTemplate) {
            $this->normalizeTemplateOrders();
            $formTemplate->refresh();

            $oldOrder = max(1, (int) $formTemplate->sort_order);
            $maxOrder = max(1, (int) FormTemplate::count());
            $requestedOrder = max(1, (int) ($data['sort_order'] ?? $oldOrder));
            $requestedOrder = min($requestedOrder, $maxOrder);

            if ($requestedOrder !== $oldOrder) {
                $occupied = FormTemplate::where('id', '!=', $formTemplate->getKey())
                    ->where('sort_order', $requestedOrder)
                    ->first();

                // Exact swap: the template already using the requested order takes
                // the edited template's previous order.
                if ($occupied) {
                    // Free the old slot first, then complete the swap. This also works
                    // if sort_order later receives a unique database index.
                    $formTemplate->update(['sort_order' => 100000 + (int) $formTemplate->getKey()]);
                    $occupied->update(['sort_order' => $oldOrder]);
                }
            }

            $templateData = collect($data)->except('fields')->all();
            $templateData['sort_order'] = $requestedOrder;
            $formTemplate->update($templateData);
            $this->syncFields($formTemplate, $data['fields'], true);
        });

        return response()->json(['message' => 'Form template updated successfully.']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'distinct', 'exists:form_templates,id'],
        ]);

        DB::transaction(function () use ($data) {
            $ids = array_values($data['order']);

            // Move to temporary values first so this remains safe even if a unique
            // database index is added to sort_order later.
            foreach ($ids as $index => $id) {
                FormTemplate::whereKey($id)->update(['sort_order' => 100000 + $index]);
            }

            foreach ($ids as $index => $id) {
                FormTemplate::whereKey($id)->update(['sort_order' => $index + 1]);
            }

            // Append any templates not included in the submitted DOM order.
            $next = count($ids) + 1;
            FormTemplate::whereNotIn('id', $ids)
                ->orderBy('sort_order')->orderBy('id')
                ->get()
                ->each(function (FormTemplate $template) use (&$next) {
                    $template->update(['sort_order' => $next++]);
                });
        });

        return response()->json(['message' => 'Template display order updated.']);
    }

    public function destroy(FormTemplate $formTemplate): JsonResponse
    {
        if ($this->isSystemManagedTemplate($formTemplate)) {
            return response()->json([
                'message' => 'This fixed employment form is required by the public application flow and Assessment Insights and cannot be deleted.',
            ], 422);
        }

        if ($formTemplate->submissions()->exists()) {
            return response()->json([
                'message' => 'This template already has submissions. Deactivate it instead of deleting it.'
            ], 422);
        }

        $formTemplate->delete();
        return response()->json(['message' => 'Form template deleted successfully.']);
    }


    private function isSystemManagedTemplate(FormTemplate $template): bool
    {
        return $this->isReservedTemplateIdentity((string) $template->name, (string) $template->type);
    }

    private function isReservedTemplateIdentity(string $name, string $type): bool
    {
        return ($type === 'application' && $name === 'Application for Employment')
            || ($type === 'questionnaire' && $name === 'Employment Questionnaire');
    }

    private function validated(Request $request, ?FormTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['application', 'questionnaire'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
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

    private function normalizeTemplateOrders(): void
    {
        $templates = FormTemplate::query()
            ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order < 1 THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($templates as $index => $template) {
            $expected = $index + 1;
            if ((int) $template->sort_order !== $expected) {
                $template->update(['sort_order' => $expected]);
            }
        }
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
