<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Term\StoreTermRequest;
use App\Http\Requests\Api\Term\UpdateTermRequest;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermController extends BaseApiController
{
    /**
     * Display a listing of terms
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // For parents, show terms from all schools (they may have children in different schools)
        // For other users, filter by school_id
        if ($user->isParent()) {
            $query = Term::with('academicYear');
        } else {
            $schoolId = $request->get('school_id');
            if (!$schoolId) {
                return $this->error('School ID is required', 400);
            }
            
            $query = Term::whereHas('academicYear', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->with('academicYear');
        }

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->get('academic_year_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $terms = $query->orderBy('start_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($terms, 'Terms retrieved successfully');
    }

    /**
     * Store a newly created term
     */
    public function store(StoreTermRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Check if academic year belongs to school
        $academicYear = \App\Models\AcademicYear::findOrFail($data['academic_year_id']);
        if ($academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Academic year not found', 404);
        }

        // Check if term number already exists for this academic year
        $existingTerm = Term::where('academic_year_id', $data['academic_year_id'])
            ->where('term_number', $data['term_number'])
            ->first();

        if ($existingTerm) {
            return $this->error("Term {$data['term_number']} already exists for this academic year", 422);
        }

        $term = Term::create($data);
        $term->load('academicYear');

        return $this->success($term, 'Term created successfully', 201);
    }

    /**
     * Display the specified term
     */
    public function show(Request $request, Term $term): JsonResponse
    {
        if ($term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Term not found', 404);
        }

        $term->load(['academicYear', 'assessments', 'subscriptions']);

        return $this->success($term, 'Term retrieved successfully');
    }

    /**
     * Update the specified term
     */
    public function update(UpdateTermRequest $request, Term $term): JsonResponse
    {
        if ($term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Term not found', 404);
        }

        // Don't allow updating closed or archived terms
        if (in_array($term->status, ['closed', 'archived'])) {
            return $this->error("Cannot update term. Term status is '{$term->status}'.", 403);
        }

        $term->update($request->validated());
        $term->load('academicYear');

        return $this->success($term, 'Term updated successfully');
    }

    /**
     * Remove the specified term
     */
    public function destroy(Request $request, Term $term): JsonResponse
    {
        if ($term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Term not found', 404);
        }

        // Don't allow deleting active or closed terms
        if (in_array($term->status, ['active', 'closed', 'archived'])) {
            return $this->error("Cannot delete term. Term status is '{$term->status}'.", 403);
        }

        $term->delete();

        return $this->success(null, 'Term deleted successfully');
    }

    /**
     * Activate a term
     */
    public function activate(Request $request, Term $term): JsonResponse
    {
        if ($term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Term not found', 404);
        }

        // Deactivate other terms in the same academic year
        Term::where('academic_year_id', $term->academic_year_id)
            ->where('id', '!=', $term->id)
            ->where('status', 'active')
            ->update(['status' => 'closed']);

        $term->status = 'active';
        $term->save();

        return $this->success($term, 'Term activated successfully');
    }

    /**
     * Start closing a term (grace period)
     */
    public function startClosing(Request $request, Term $term): JsonResponse
    {
        if ($term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Term not found', 404);
        }

        if ($term->status !== 'active') {
            return $this->error("Term must be active to start closing. Current status: '{$term->status}'", 422);
        }

        $term->startClosing();

        return $this->success($term, 'Term closing process started');
    }

    /**
     * Close a term
     */
    public function close(Request $request, Term $term): JsonResponse
    {
        if ($term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Term not found', 404);
        }

        if (!in_array($term->status, ['active', 'closing'])) {
            return $this->error("Term must be active or closing to be closed. Current status: '{$term->status}'", 422);
        }

        $term->close();

        return $this->success($term, 'Term closed successfully');
    }

    /**
     * Archive a term
     */
    public function archive(Request $request, Term $term): JsonResponse
    {
        if ($term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Term not found', 404);
        }

        if ($term->status !== 'closed') {
            return $this->error("Term must be closed to be archived. Current status: '{$term->status}'", 422);
        }

        $term->archive();

        return $this->success($term, 'Term archived successfully');
    }
}

