<?php

namespace App\Http\Controllers\Api;

use App\Models\Fee;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeeController extends BaseApiController
{
    /**
     * Display a listing of fees
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $request->get('school_id');
        
        $query = Fee::where('school_id', $schoolId);

        if ($request->has('term_id')) {
            $query->where('term_id', $request->get('term_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }

        if ($request->has('level_category')) {
            $query->where('level_category', $request->get('level_category'));
        }

        if ($request->has('fee_type')) {
            $feeType = $request->get('fee_type');
            if ($feeType === 'class') {
                $query->whereNotNull('class_id');
            } elseif ($feeType === 'level') {
                $query->whereNotNull('level_category')->whereNull('class_id');
            } elseif ($feeType === 'school') {
                $query->whereNull('class_id')->whereNull('level_category');
            }
        }

        $fees = $query->with(['term.academicYear', 'class'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($fees, 'Fees retrieved successfully');
    }

    /**
     * Store a newly created fee
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'fee_type' => ['required', 'in:school,level,class'],
            'class_id' => ['required_if:fee_type,class', 'nullable', 'exists:classes,id'],
            'level_category' => ['required_if:fee_type,level', 'nullable', 'in:nursery,creche,primary,jhs,shs'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'is_active' => ['nullable', 'boolean'],
            'due_date' => ['nullable', 'date'],
        ]);

        $schoolId = $request->get('school_id');
        $feeType = $request->get('fee_type');
        $classId = $request->get('class_id');
        $levelCategory = $request->get('level_category');

        // Validate class belongs to school
        if ($classId) {
            $class = \App\Models\ClassModel::find($classId);
            if (!$class || $class->school_id !== $schoolId) {
                return $this->error('Class not found or does not belong to this school', 422);
            }
        }

        // Check if fee with same name already exists for this term and scope
        $existingFee = Fee::where('school_id', $schoolId)
            ->where('term_id', $request->get('term_id'))
            ->where('name', $request->get('name'))
            ->where('class_id', $classId)
            ->where('level_category', $levelCategory)
            ->first();

        if ($existingFee) {
            return $this->error('A fee with this name already exists for this term and scope', 422);
        }

        $fee = Fee::create([
            'school_id' => $schoolId,
            'term_id' => $request->get('term_id'),
            'class_id' => $feeType === 'class' ? $classId : null,
            'level_category' => $feeType === 'level' ? $levelCategory : null,
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'amount' => $request->get('amount'),
            'currency' => $request->get('currency', 'GHS'),
            'is_active' => $request->get('is_active', true),
            'due_date' => $request->get('due_date'),
        ]);

        $fee->load(['term.academicYear', 'class']);

        return $this->success($fee, 'Fee created successfully', 201);
    }

    /**
     * Display the specified fee
     */
    public function show(Request $request, Fee $fee): JsonResponse
    {
        if ($fee->school_id !== $request->get('school_id')) {
            return $this->error('Fee not found', 404);
        }

        $fee->load(['term.academicYear', 'class']);

        return $this->success($fee, 'Fee retrieved successfully');
    }

    /**
     * Update the specified fee
     */
    public function update(Request $request, Fee $fee): JsonResponse
    {
        if ($fee->school_id !== $request->get('school_id')) {
            return $this->error('Fee not found', 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'is_active' => ['nullable', 'boolean'],
            'due_date' => ['nullable', 'date'],
        ]);

        // Check if name change would create duplicate
        if ($request->has('name') && $request->get('name') !== $fee->name) {
            $existingFee = Fee::where('school_id', $fee->school_id)
                ->where('term_id', $fee->term_id)
                ->where('name', $request->get('name'))
                ->where('class_id', $fee->class_id)
                ->where('level_category', $fee->level_category)
                ->where('id', '!=', $fee->id)
                ->first();

            if ($existingFee) {
                return $this->error('A fee with this name already exists for this term and scope', 422);
            }
        }

        $fee->update($request->only([
            'name',
            'description',
            'amount',
            'currency',
            'is_active',
            'due_date',
        ]));

        $fee->load(['term.academicYear', 'class']);

        return $this->success($fee, 'Fee updated successfully');
    }

    /**
     * Remove the specified fee
     */
    public function destroy(Request $request, Fee $fee): JsonResponse
    {
        if ($fee->school_id !== $request->get('school_id')) {
            return $this->error('Fee not found', 404);
        }

        $fee->delete();

        return $this->success(null, 'Fee deleted successfully');
    }

    /**
     * Get fee for a specific term (for parents)
     * Uses student_id to find the applicable fee (class > level > school)
     */
    public function getTermFee(Request $request, $termId): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $studentId = $request->get('student_id');
        
        // Get the term to find school_id
        $term = \App\Models\Term::find($termId);
        if (!$term) {
            return $this->error('Term not found', 404);
        }

        // Find applicable fee using priority: class > level > school
        $fee = Fee::findApplicableFee($studentId, $termId);

        if (!$fee) {
            return $this->success([
                'term_id' => $termId,
                'student_id' => $studentId,
                'amount' => null,
                'message' => 'No fee configured for this term',
            ], 'No fee found for this term');
        }

        return $this->success([
            'fee' => $fee->load('class'),
            'term' => $term->load('academicYear'),
        ], 'Fee retrieved successfully');
    }
}

