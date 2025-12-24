<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Result\EnterResultRequest;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends BaseApiController
{
    /**
     * Display a listing of results
     */
    public function index(Request $request): JsonResponse
    {
        $query = Result::whereHas('assessment', function ($q) use ($request) {
            $q->whereHas('term', function ($query) use ($request) {
                $query->whereHas('academicYear', function ($q) use ($request) {
                    $q->where('school_id', $request->get('school_id'));
                });
            });
        })->with(['assessment', 'student', 'enteredBy']);

        if ($request->has('assessment_id')) {
            $query->where('assessment_id', $request->get('assessment_id'));
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->get('student_id'));
        }

        $results = $query->paginate($request->get('per_page', 15));

        return $this->paginated($results, 'Results retrieved successfully');
    }

    /**
     * Enter results for students
     */
    public function enter(EnterResultRequest $request): JsonResponse
    {
        $data = $request->validated();
        $results = [];

        foreach ($data['results'] as $resultData) {
            $results[] = Result::updateOrCreate(
                [
                    'assessment_id' => $data['assessment_id'],
                    'student_id' => $resultData['student_id'],
                ],
                [
                    'marks_obtained' => $resultData['marks_obtained'],
                    'grade' => $this->calculateGrade($resultData['marks_obtained'], $data['total_marks']),
                    'remarks' => $resultData['remarks'] ?? null,
                    'entered_by' => auth()->id(),
                    'entered_at' => now(),
                ]
            );
        }

        return $this->success($results, 'Results entered successfully', 201);
    }

    /**
     * Get student results
     */
    public function studentResults(Request $request, $studentId): JsonResponse
    {
        $student = Student::findOrFail($studentId);
        
        // Check subscription if parent
        if (auth()->user()->isParent()) {
            $termId = $request->get('term_id');
            if ($termId && !auth()->user()->parent->hasActiveSubscription($studentId, $termId)) {
                return $this->error('Subscription required to view results for this term', 403);
            }
        }

        $query = $student->results()->with(['assessment.term', 'assessment.classSubject.subject']);

        if ($request->has('term_id')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('term_id', $request->get('term_id'));
            });
        }

        $results = $query->orderBy('created_at', 'desc')->get();

        return $this->success($results, 'Student results retrieved successfully');
    }

    /**
     * Get student results for specific term
     */
    public function studentTermResults(Request $request, $studentId, $termId): JsonResponse
    {
        $student = Student::findOrFail($studentId);
        
        // Check subscription if parent
        if (auth()->user()->isParent()) {
            if (!auth()->user()->parent->hasActiveSubscription($studentId, $termId)) {
                return $this->error('Subscription required to view results for this term', 403);
            }
        }

        $results = $student->results()
            ->whereHas('assessment', function ($q) use ($termId) {
                $q->where('term_id', $termId);
            })
            ->with(['assessment.classSubject.subject'])
            ->get();

        return $this->success($results, 'Student term results retrieved successfully');
    }

    /**
     * Get assessment results
     */
    public function assessmentResults(Request $request, $assessmentId): JsonResponse
    {
        $results = Result::where('assessment_id', $assessmentId)
            ->with(['student'])
            ->get();

        return $this->success($results, 'Assessment results retrieved successfully');
    }

    /**
     * Calculate grade based on marks using school's grading scale
     */
    private function calculateGrade($marksObtained, $totalMarks): string
    {
        $percentage = ($marksObtained / $totalMarks) * 100;

        // Get the school's default grading scale
        $schoolId = request()->get('school_id');
        if ($schoolId) {
            $gradingScale = \App\Models\GradingScale::getDefaultForSchool($schoolId);
            if ($gradingScale) {
                return $gradingScale->getGradeForPercentage($percentage);
            }
        }

        // Fallback to default grading if no scale is configured
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        if ($percentage >= 40) return 'E';
        return 'F';
    }
}

