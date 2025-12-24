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
        $query = Assessment::whereHas('term', function ($q) use ($request) {
            $q->whereHas('academicYear', function ($query) use ($request) {
                $query->where('school_id', $request->get('school_id'));
            });
        })->with(['term', 'classSubject.subject', 'classSubject.class', 'teacher.user']);

        if ($request->has('term_id')) {
            $query->where('term_id', $request->get('term_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
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
        // Check if term allows modifications
        if (!$assessment->term->allowsNewAssessments()) {
            return $this->error("Cannot update assessment. Term status is '{$assessment->term->status}'.", 403);
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
        // Check if term allows deletion
        if (!$assessment->term->allowsNewAssessments()) {
            return $this->error("Cannot delete assessment. Term status is '{$assessment->term->status}'.", 403);
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

