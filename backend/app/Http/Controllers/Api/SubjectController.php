<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Subject\StoreSubjectRequest;
use App\Http\Requests\Api\Subject\UpdateSubjectRequest;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends BaseApiController
{
    /**
     * Display a listing of subjects
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subject::where('school_id', $request->get('school_id'));

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_core')) {
            $query->where('is_core', $request->boolean('is_core'));
        }

        $subjects = $query->paginate($request->get('per_page', 15));

        return $this->paginated($subjects, 'Subjects retrieved successfully');
    }

    /**
     * Store a newly created subject
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['school_id'] = $request->get('school_id');

        $subject = Subject::create($data);

        return $this->success($subject, 'Subject created successfully', 201);
    }

    /**
     * Display the specified subject
     */
    public function show(Request $request, Subject $subject): JsonResponse
    {
        if ($subject->school_id !== $request->get('school_id')) {
            return $this->error('Subject not found', 404);
        }

        $subject->load(['classSubjects.class', 'classSubjects.teacher.user', 'classSubjects.academicYear']);

        return $this->success($subject, 'Subject retrieved successfully');
    }

    /**
     * Update the specified subject
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        if ($subject->school_id !== $request->get('school_id')) {
            return $this->error('Subject not found', 404);
        }

        $subject->update($request->validated());

        return $this->success($subject, 'Subject updated successfully');
    }

    /**
     * Remove the specified subject
     */
    public function destroy(Request $request, Subject $subject): JsonResponse
    {
        if ($subject->school_id !== $request->get('school_id')) {
            return $this->error('Subject not found', 404);
        }

        $subject->delete();

        return $this->success(null, 'Subject deleted successfully');
    }
}

