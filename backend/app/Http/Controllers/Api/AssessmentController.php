<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Assessment\StoreAssessmentRequest;
use App\Http\Requests\Api\Assessment\UpdateAssessmentRequest;
use App\Models\Assessment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends BaseApiController
{
    /**
     * Display a listing of assessments
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $schoolId = $request->get('school_id');
        
        // Check subscription and parent-child relationship if parent is viewing assessments for a specific student
        if ($user->isParent() && $request->has('student_id')) {
            $studentId = $request->get('student_id');
            $termId = $request->get('term_id');
            $parent = $user->parent;
            
            // Verify the student is linked to this parent
            if (!$parent->students()->where('students.id', $studentId)->exists()) {
                return $this->error('Student not found or not linked to your account', 404);
            }
            
            if ($termId && !$parent->hasActiveSubscription($studentId, $termId)) {
                return $this->error('Subscription required to view assessments for this term', 403);
            }
            
            // For parents, get school_id from the student instead of request
            $student = \App\Models\Student::find($studentId);
            if ($student) {
                $schoolId = $student->school_id;
            }
        }
        
        // Build query - if school_id is available, scope by school; otherwise allow all (for parents)
        $query = Assessment::with(['term', 'classSubject.subject', 'classSubject.class', 'teacher.user']);
        
        if ($schoolId) {
            $query->whereHas('term', function ($q) use ($schoolId) {
                $q->whereHas('academicYear', function ($query) use ($schoolId) {
                    $query->where('school_id', $schoolId);
                });
            });
        }

        if ($request->has('term_id')) {
            $query->where('term_id', $request->get('term_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // If student_id is provided, filter assessments for that student's class
        if ($request->has('student_id')) {
            $student = \App\Models\Student::find($request->get('student_id'));
            if ($student) {
                // Get student's current enrollment class
                $enrollment = $student->enrollments()
                    ->whereHas('class.academicYear.terms', function ($q) use ($request) {
                        if ($request->has('term_id')) {
                            $q->where('terms.id', $request->get('term_id'));
                        }
                    })
                    ->latest()
                    ->first();
                
                if ($enrollment && $enrollment->class_id) {
                    $query->whereHas('classSubject', function ($q) use ($enrollment) {
                        $q->where('class_id', $enrollment->class_id);
                    });
                }
            }
        }

        $assessments = $query->orderBy('assessment_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($assessments, 'Assessments retrieved successfully');
    }

    /**
     * Store a newly created assessment
     */
    public function store(StoreAssessmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['teacher_id'] = auth()->user()->teacher->id ?? null;

        // Check if term allows new assessments
        $term = \App\Models\Term::findOrFail($data['term_id']);
        if (!$term->allowsNewAssessments()) {
            return $this->error("Cannot create assessment. Term status is '{$term->status}'. Only 'draft' and 'active' terms allow new assessments.", 403);
        }

        $assessment = Assessment::create($data);
        $assessment->load(['term', 'classSubject.subject', 'classSubject.class', 'teacher.user']);

        return $this->success($assessment, 'Assessment created successfully', 201);
    }

    /**
     * Display the specified assessment
     */
    public function show(Request $request, Assessment $assessment): JsonResponse
    {
        $assessment->load(['term', 'classSubject.subject', 'classSubject.class', 'teacher.user', 'results.student']);

        return $this->success($assessment, 'Assessment retrieved successfully');
    }

    /**
     * Update the specified assessment
     */
    public function update(UpdateAssessmentRequest $request, Assessment $assessment): JsonResponse
    {
        $user = auth()->user();
        
        // Prevent editing finalized assessments (unless explicitly finalizing)
        if ($assessment->is_finalized && !$request->has('is_finalized')) {
            return $this->error('Cannot update a finalized assessment. It has already been finalized.', 403);
        }

        // Check if term allows modifications
        if (!$assessment->term->allowsNewAssessments()) {
            return $this->error("Cannot update assessment. Term status is '{$assessment->term->status}'.", 403);
        }

        // Teachers can only edit their own assessments
        if ($user->isTeacher() && $assessment->teacher_id !== $user->teacher->id) {
            return $this->error('You can only edit assessments that you created.', 403);
        }

        $assessment->update($request->validated());
        $assessment->load(['term', 'classSubject.subject', 'classSubject.class', 'teacher.user']);

        return $this->success($assessment, 'Assessment updated successfully');
    }

    /**
     * Remove the specified assessment
     */
    public function destroy(Request $request, Assessment $assessment): JsonResponse
    {
        $user = auth()->user();
        
        // Prevent deleting finalized assessments
        if ($assessment->is_finalized) {
            return $this->error('Cannot delete a finalized assessment.', 403);
        }

        // Check if term allows deletion
        if (!$assessment->term->allowsNewAssessments()) {
            return $this->error("Cannot delete assessment. Term status is '{$assessment->term->status}'.", 403);
        }

        // Teachers can only delete their own assessments
        if ($user->isTeacher() && $assessment->teacher_id !== $user->teacher->id) {
            return $this->error('You can only delete assessments that you created.', 403);
        }

        $assessment->delete();

        return $this->success(null, 'Assessment deleted successfully');
    }

    /**
     * Get assessment results
     */
    public function results(Request $request, Assessment $assessment): JsonResponse
    {
        $results = $assessment->results()->with('student')->get();

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

        return $this->success($formattedResults, 'Assessment results retrieved successfully');
    }
}

