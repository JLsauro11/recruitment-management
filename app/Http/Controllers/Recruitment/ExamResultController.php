<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ExamResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamResultController extends Controller
{
    public function index(): View
    {
        $applications = Application::with(['applicant', 'vacancy'])->latest('applied_at')->get();
        return view('recruitment.exams.index', compact('applications'));
    }

    public function data(): JsonResponse
    {
        $data = ExamResult::with(['application.applicant', 'application.vacancy'])
            ->latest()->get()->map(fn (ExamResult $r) => [
                'id' => $r->id,
                'reference_no' => $r->application?->reference_no ?? 'N/A',
                'applicant' => $r->application?->applicant?->full_name ?? 'N/A',
                'position' => $r->application?->vacancy?->title ?? 'N/A',
                'exam_type' => $r->exam_type,
                'score' => (float) $r->score,
                'passing_score' => (float) $r->passing_score,
                'result' => $r->result,
                'date' => $r->created_at?->format('M d, Y'),
            ]);
        return response()->json(['data' => $data]);
    }

    public function show(ExamResult $examResult): JsonResponse
    {
        return response()->json($examResult);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['result'] = $data['score'] >= $data['passing_score'] ? 'Passed' : 'Failed';
        $result = ExamResult::updateOrCreate(
            ['application_id' => $data['application_id'], 'exam_type' => $data['exam_type']],
            $data
        );
        $this->syncApplication($result);
        return response()->json(['message' => 'Exam result saved successfully.']);
    }

    public function update(Request $request, ExamResult $examResult): JsonResponse
    {
        $data = $this->validated($request);
        $data['result'] = $data['score'] >= $data['passing_score'] ? 'Passed' : 'Failed';
        $examResult->update($data);
        $this->syncApplication($examResult);
        return response()->json(['message' => 'Exam result updated successfully.']);
    }

    public function destroy(ExamResult $examResult): JsonResponse
    {
        $examResult->delete();
        return response()->json(['message' => 'Exam result deleted successfully.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'application_id' => ['required', 'exists:applications,id'],
            'exam_type' => ['required', 'string', 'max:150'],
            'score' => ['required', 'numeric', 'min:0'],
            'passing_score' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function syncApplication(ExamResult $result): void
    {
        $application = $result->application;
        if (!$application) return;
        $status = $result->result === 'Passed' ? 'For Final Interview' : 'Rejected';
        $application->update(['status' => $status]);
        $application->statusHistories()->create([
            'status' => $status,
            'remarks' => $result->exam_type . ': ' . $result->result . ' (' . $result->score . '/' . $result->passing_score . ')',
            'updated_by' => auth()->id(),
        ]);
    }
}
