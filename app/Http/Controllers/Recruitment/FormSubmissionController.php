<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FormSubmissionController extends Controller
{
    public function index(): View
    {
        return view('recruitment.form-submissions.index');
    }

    public function data(): JsonResponse
    {
        $data = FormSubmission::with([
            'template', 'application.applicant', 'application.vacancy.position.department'
        ])->latest('submitted_at')->get()->map(fn ($submission) => [
            'id' => $submission->id,
            'reference_no' => $submission->application?->reference_no ?? 'N/A',
            'applicant' => $submission->application?->applicant?->full_name ?? 'N/A',
            'position' => $submission->application?->vacancy?->title ?? 'N/A',
            'template' => $submission->template?->name ?? 'N/A',
            'type' => $submission->template?->type ?? 'N/A',
            'submitted_at' => $submission->submitted_at?->format('M d, Y g:i A'),
        ]);

        return response()->json(['data' => $data]);
    }

    public function show(FormSubmission $formSubmission): JsonResponse
    {
        $formSubmission->load([
            'template.fields', 'answers.field',
            'application.applicant', 'application.vacancy.position.department'
        ]);

        return response()->json([
            'id' => $formSubmission->id,
            'template' => $formSubmission->template,
            'application' => $formSubmission->application,
            'answers' => $formSubmission->answers->map(fn ($answer) => [
                'field_id' => $answer->form_field_id,
                'section' => $answer->field?->section,
                'label' => $answer->field?->label,
                'value' => $answer->value,
                'sort_order' => $answer->field?->sort_order,
            ])->sortBy('sort_order')->values(),
        ]);
    }
}
