<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AcademicYear\StoreAcademicYearRequest;
use App\Http\Requests\Api\AcademicYear\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicYearController extends BaseApiController
{
    /**
     * Display a listing of academic years
     */
    public function index(Request $request): JsonResponse
    {
        $query = AcademicYear::where('school_id', $request->get('school_id'))
            ->with('terms');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $academicYears = $query->orderBy('start_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($academicYears, 'Academic years retrieved successfully');
    }

    /**
     * Store a newly created academic year
     */
    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['school_id'] = $request->get('school_id');

        // If this is set as active, deactivate others
        if ($request->boolean('is_active')) {
            AcademicYear::where('school_id', $data['school_id'])
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $academicYear = AcademicYear::create($data);
        $academicYear->load('terms');

        return $this->success($academicYear, 'Academic year created successfully', 201);
    }

    /**
     * Display the specified academic year
     */
    public function show(Request $request, AcademicYear $academicYear): JsonResponse
    {
        if ($academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Academic year not found', 404);
        }

        $academicYear->load('terms');

        return $this->success($academicYear, 'Academic year retrieved successfully');
    }

    /**
     * Update the specified academic year
     */
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): JsonResponse
    {
        if ($academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Academic year not found', 404);
        }

        $data = $request->validated();

        // If this is set as active, deactivate others
        if (isset($data['is_active']) && $data['is_active']) {
            AcademicYear::where('school_id', $academicYear->school_id)
                ->where('id', '!=', $academicYear->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $academicYear->update($data);
        $academicYear->load('terms');

        return $this->success($academicYear, 'Academic year updated successfully');
    }

    /**
     * Remove the specified academic year
     */
    public function destroy(Request $request, AcademicYear $academicYear): JsonResponse
    {
        if ($academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Academic year not found', 404);
        }

        // Don't allow deleting active academic year
        if ($academicYear->is_active) {
            return $this->error('Cannot delete active academic year', 403);
        }

        $academicYear->delete();

        return $this->success(null, 'Academic year deleted successfully');
    }

    /**
     * Activate an academic year
     */
    public function activate(Request $request, AcademicYear $academicYear): JsonResponse
    {
        if ($academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Academic year not found', 404);
        }

        // Deactivate other academic years
        AcademicYear::where('school_id', $academicYear->school_id)
            ->where('id', '!=', $academicYear->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $academicYear->is_active = true;
        $academicYear->save();

        return $this->success($academicYear, 'Academic year activated successfully');
    }
}

