<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Exam\StoreExamRequest;
use App\Http\Requests\Api\Exam\UpdateExamRequest;
use App\Models\Assessment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends BaseApiController
{
    /**
     * Display a listing of exams
     */
    public function index(Request $request): JsonResponse
    {
        $query = Assessment::where('type', 'exam')
            ->whereHas('term', function ($q) use ($request) {
                $q->whereHas('academicYear', function ($query) use ($request) {
                    $query->where('school_id', $request->get('school_id'));
                });
            })
            ->with(['term', 'classSubject.subject', 'classSubject.class', 'teacher.user']);

        if ($request->has('term_id')) {
            $query->where('term_id', $request->get('term_id'));
        }

        if ($request->has('class_id')) {
            $query->whereHas('classSubject.class', function ($q) use ($request) {
                $q->where('id', $request->get('class_id'));
            });
        }

        $exams = $query->orderBy('assessment_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($exams, 'Exams retrieved successfully');
    }

    /**
     * Store a newly created exam
     */
    public function store(StoreExamRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['type'] = 'exam';
        $data['teacher_id'] = auth()->user()->teacher->id ?? null;

        $exam = Assessment::create($data);
        $exam->load(['term', 'classSubject.subject', 'classSubject.class', 'teacher.user']);

        return $this->success($exam, 'Exam created successfully', 201);
    }

    /**
     * Display the specified exam
     */
    public function show(Request $request, Assessment $exam): JsonResponse
    {
        if ($exam->type !== 'exam') {
            return $this->error('Exam not found', 404);
        }

        $exam->load(['term', 'classSubject.subject', 'classSubject.class', 'teacher.user', 'results.student']);

        return $this->success($exam, 'Exam retrieved successfully');
    }

    /**
     * Update the specified exam
     */
    public function update(UpdateExamRequest $request, Assessment $exam): JsonResponse
    {
        if ($exam->type !== 'exam') {
            return $this->error('Exam not found', 404);
        }

        $user = auth()->user();

        // Prevent editing finalized exams (unless explicitly finalizing)
        if ($exam->is_finalized && !$request->has('is_finalized')) {
            return $this->error('Cannot update a finalized exam. It has already been verified.', 403);
        }

        // Check if term allows modifications
        if (!$exam->term->allowsNewAssessments()) {
            return $this->error("Cannot update exam. Term status is '{$exam->term->status}'.", 403);
        }

        // Teachers can only edit their own exams
        if ($user->isTeacher() && $exam->teacher_id !== $user->teacher->id && !$user->isSchoolAdmin() && !$user->isSuperAdmin()) {
            return $this->error('You can only edit exams that you created.', 403);
        }

        $exam->update($request->validated());
        $exam->load(['term', 'classSubject.subject', 'classSubject.class', 'teacher.user']);

        return $this->success($exam, 'Exam updated successfully');
    }

    /**
     * Remove the specified exam
     */
    public function destroy(Request $request, Assessment $exam): JsonResponse
    {
        if ($exam->type !== 'exam') {
            return $this->error('Exam not found', 404);
        }

        $user = auth()->user();

        // Prevent deleting finalized exams
        if ($exam->is_finalized) {
            return $this->error('Cannot delete a finalized exam. It has already been verified.', 403);
        }

        // Check if term allows deletion
        if (!$exam->term->allowsNewAssessments()) {
            return $this->error("Cannot delete exam. Term status is '{$exam->term->status}'.", 403);
        }

        // Teachers can only delete their own exams
        if ($user->isTeacher() && $exam->teacher_id !== $user->teacher->id && !$user->isSchoolAdmin() && !$user->isSuperAdmin()) {
            return $this->error('You can only delete exams that you created.', 403);
        }

        $exam->delete();

        return $this->success(null, 'Exam deleted successfully');
    }

    /**
     * Get exam results
     */
    public function results(Request $request, Assessment $exam): JsonResponse
    {
        if ($exam->type !== 'exam') {
            return $this->error('Exam not found', 404);
        }

        $results = $exam->results()->with('student')->get();

        // Format results to ensure student full_name is included
        $formattedResults = $results->map(function ($result) {
            return [
                'id' => $result->id,
                'student_id' => $result->student_id,
                'assessment_id' => $result->assessment_id,
                'marks_obtained' => $result->marks_obtained,
                'grade' => $result->grade,
                'remarks' => $result->remarks,
                'entered_by' => $result->entered_by,
                'entered_at' => $result->entered_at,
                'created_at' => $result->created_at,
                'updated_at' => $result->updated_at,
                'student' => $result->student ? [
                    'id' => $result->student->id,
                    'student_number' => $result->student->student_number,
                    'first_name' => $result->student->first_name,
                    'middle_name' => $result->student->middle_name,
                    'last_name' => $result->student->last_name,
                    'full_name' => $result->student->full_name,
                    'email' => $result->student->email,
                    'phone' => $result->student->phone,
                ] : null,
            ];
        });

        return $this->success($formattedResults, 'Exam results retrieved successfully');
    }
}

