<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\GradingScale\StoreGradingScaleRequest;
use App\Http\Requests\Api\GradingScale\UpdateGradingScaleRequest;
use App\Models\GradingScale;
use App\Models\GradeLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradingScaleController extends BaseApiController
{
    /**
     * Display a listing of grading scales
     */
    public function index(Request $request): JsonResponse
    {
        $query = GradingScale::where('school_id', $request->get('school_id'))
            ->with('gradeLevels');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $gradingScales = $query->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return $this->success($gradingScales, 'Grading scales retrieved successfully');
    }

    /**
     * Store a newly created grading scale
     */
    public function store(StoreGradingScaleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['school_id'] = $request->get('school_id');

        // If this is set as default, unset other defaults
        if ($data['is_default'] ?? false) {
            GradingScale::where('school_id', $data['school_id'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $gradingScale = GradingScale::create($data);

        // Create grade levels
        if ($request->has('grade_levels') && is_array($request->grade_levels)) {
            foreach ($request->grade_levels as $levelData) {
                GradeLevel::create([
                    'grading_scale_id' => $gradingScale->id,
                    'grade' => $levelData['grade'],
                    'label' => $levelData['label'] ?? null,
                    'min_percentage' => $levelData['min_percentage'],
                    'max_percentage' => $levelData['max_percentage'] ?? null,
                    'gpa_value' => $levelData['gpa_value'] ?? null,
                    'description' => $levelData['description'] ?? null,
                    'order' => $levelData['order'] ?? 0,
                ]);
            }
        }

        $gradingScale->load('gradeLevels');

        return $this->success($gradingScale, 'Grading scale created successfully', 201);
    }

    /**
     * Display the specified grading scale
     */
    public function show(Request $request, GradingScale $gradingScale): JsonResponse
    {
        if ($gradingScale->school_id !== $request->get('school_id')) {
            return $this->error('Grading scale not found', 404);
        }

        $gradingScale->load('gradeLevels');

        return $this->success($gradingScale, 'Grading scale retrieved successfully');
    }

    /**
     * Update the specified grading scale
     */
    public function update(UpdateGradingScaleRequest $request, GradingScale $gradingScale): JsonResponse
    {
        if ($gradingScale->school_id !== $request->get('school_id')) {
            return $this->error('Grading scale not found', 404);
        }

        $data = $request->validated();

        // If this is set as default, unset other defaults
        if (isset($data['is_default']) && $data['is_default']) {
            GradingScale::where('school_id', $gradingScale->school_id)
                ->where('id', '!=', $gradingScale->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $gradingScale->update($data);

        // Update grade levels if provided
        if ($request->has('grade_levels') && is_array($request->grade_levels)) {
            // Delete existing grade levels
            $gradingScale->gradeLevels()->delete();

            // Create new grade levels
            foreach ($request->grade_levels as $levelData) {
                GradeLevel::create([
                    'grading_scale_id' => $gradingScale->id,
                    'grade' => $levelData['grade'],
                    'label' => $levelData['label'] ?? null,
                    'min_percentage' => $levelData['min_percentage'],
                    'max_percentage' => $levelData['max_percentage'] ?? null,
                    'gpa_value' => $levelData['gpa_value'] ?? null,
                    'description' => $levelData['description'] ?? null,
                    'order' => $levelData['order'] ?? 0,
                ]);
            }
        }

        $gradingScale->load('gradeLevels');

        return $this->success($gradingScale, 'Grading scale updated successfully');
    }

    /**
     * Remove the specified grading scale
     */
    public function destroy(Request $request, GradingScale $gradingScale): JsonResponse
    {
        if ($gradingScale->school_id !== $request->get('school_id')) {
            return $this->error('Grading scale not found', 404);
        }

        // Prevent deletion of default grading scale if it's the only one
        $otherScales = GradingScale::where('school_id', $gradingScale->school_id)
            ->where('id', '!=', $gradingScale->id)
            ->where('is_active', true)
            ->count();

        if ($gradingScale->is_default && $otherScales === 0) {
            return $this->error('Cannot delete the default grading scale. Please set another scale as default first.', 422);
        }

        $gradingScale->delete();

        return $this->success(null, 'Grading scale deleted successfully');
    }

    /**
     * Set a grading scale as default
     */
    public function setDefault(Request $request, GradingScale $gradingScale): JsonResponse
    {
        if ($gradingScale->school_id !== $request->get('school_id')) {
            return $this->error('Grading scale not found', 404);
        }

        // Unset other defaults
        GradingScale::where('school_id', $gradingScale->school_id)
            ->where('id', '!=', $gradingScale->id)
            ->update(['is_default' => false]);

        $gradingScale->update(['is_default' => true]);

        return $this->success($gradingScale, 'Grading scale set as default successfully');
    }
}

