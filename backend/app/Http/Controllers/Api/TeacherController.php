<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Teacher\StoreTeacherRequest;
use App\Http\Requests\Api\Teacher\UpdateTeacherRequest;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherController extends BaseApiController
{
    /**
     * Display a listing of teachers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Teacher::whereHas('user', function ($q) use ($request) {
            $q->where('school_id', $request->get('school_id'));
        })->with('user');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $teachers = $query->paginate($request->get('per_page', 15));

        // Format teachers for frontend
        $teachers->getCollection()->transform(function ($teacher) {
            return [
                'id' => $teacher->id,
                'staff_number' => $teacher->staff_number,
                'qualification' => $teacher->qualification,
                'specialization' => $teacher->specialization,
                'hire_date' => $teacher->hire_date?->format('Y-m-d'),
                'user' => [
                    'id' => $teacher->user->id,
                    'first_name' => $teacher->user->first_name,
                    'last_name' => $teacher->user->last_name,
                    'email' => $teacher->user->email,
                    'is_active' => $teacher->user->is_active,
                ],
            ];
        });

        return $this->paginated($teachers, 'Teachers retrieved successfully');
    }

    /**
     * Store a newly created teacher
     */
    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        // Create user account
        $user = \App\Models\User::create([
            'school_id' => $request->get('school_id'),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
        ]);

        // Assign teacher role
        $teacherRole = \App\Models\Role::where('name', 'teacher')->first();
        if ($teacherRole) {
            $user->roles()->attach($teacherRole->id);
        }

        // Create teacher profile
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'staff_number' => $data['staff_number'] ?? null,
            'qualification' => $data['qualification'] ?? null,
            'specialization' => $data['specialization'] ?? null,
            'hire_date' => $data['hire_date'] ?? null,
        ]);

        $teacher->load('user');

        return $this->success($teacher, 'Teacher created successfully', 201);
    }

    /**
     * Display the specified teacher
     */
    public function show(Request $request, Teacher $teacher): JsonResponse
    {
        if ($teacher->user->school_id !== $request->get('school_id')) {
            return $this->error('Teacher not found', 404);
        }

        $teacher->load([
            'user',
            'classes.academicYear',
            'classes.classTeacher.user',
            'classSubjects.subject',
            'classSubjects.class',
        ]);

        return $this->success($teacher, 'Teacher retrieved successfully');
    }

    /**
     * Update the specified teacher
     */
    public function update(UpdateTeacherRequest $request, Teacher $teacher): JsonResponse
    {
        if ($teacher->user->school_id !== $request->get('school_id')) {
            return $this->error('Teacher not found', 404);
        }

        $data = $request->validated();
        
        // Update user if needed
        if (isset($data['first_name']) || isset($data['last_name']) || isset($data['email'])) {
            $teacher->user->update([
                'first_name' => $data['first_name'] ?? $teacher->user->first_name,
                'last_name' => $data['last_name'] ?? $teacher->user->last_name,
                'email' => $data['email'] ?? $teacher->user->email,
            ]);
        }

        // Update teacher profile
        $teacher->update([
            'staff_number' => $data['staff_number'] ?? $teacher->staff_number,
            'qualification' => $data['qualification'] ?? $teacher->qualification,
            'specialization' => $data['specialization'] ?? $teacher->specialization,
            'hire_date' => $data['hire_date'] ?? $teacher->hire_date,
        ]);

        $teacher->load('user');

        return $this->success($teacher, 'Teacher updated successfully');
    }

    /**
     * Remove the specified teacher
     */
    public function destroy(Request $request, Teacher $teacher): JsonResponse
    {
        if ($teacher->user->school_id !== $request->get('school_id')) {
            return $this->error('Teacher not found', 404);
        }

        $teacher->delete();

        return $this->success(null, 'Teacher deleted successfully');
    }

    /**
     * Get teacher's classes
     */
    public function classes(Request $request, Teacher $teacher): JsonResponse
    {
        if ($teacher->user->school_id !== $request->get('school_id')) {
            return $this->error('Teacher not found', 404);
        }

        $classes = $teacher->classes()->with('academicYear')->get();

        return $this->success($classes, 'Teacher classes retrieved successfully');
    }

    /**
     * Assign class to teacher (as class teacher)
     */
    public function assignClass(Request $request, Teacher $teacher): JsonResponse
    {
        if ($teacher->user->school_id !== $request->get('school_id')) {
            return $this->error('Teacher not found', 404);
        }

        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
        ]);

        $class = \App\Models\ClassModel::findOrFail($request->class_id);

        // Check if class belongs to same school
        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        // Update class to assign this teacher as class teacher
        $class->update(['class_teacher_id' => $teacher->id]);

        $class->load(['academicYear', 'classTeacher.user']);

        return $this->success($class, 'Class assigned to teacher successfully');
    }

    /**
     * Assign subject to teacher
     */
    public function assignSubject(Request $request, Teacher $teacher): JsonResponse
    {
        if ($teacher->user->school_id !== $request->get('school_id')) {
            return $this->error('Teacher not found', 404);
        }

        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
        ]);

        $class = \App\Models\ClassModel::findOrFail($request->class_id);
        $subject = \App\Models\Subject::findOrFail($request->subject_id);
        $academicYear = \App\Models\AcademicYear::findOrFail($request->academic_year_id);

        // Check if all belong to same school
        if ($class->school_id !== $request->get('school_id') || 
            $subject->school_id !== $request->get('school_id') ||
            $academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Invalid class, subject, or academic year', 404);
        }

        // Create or update class subject assignment
        $classSubject = \App\Models\ClassSubject::updateOrCreate(
            [
                'class_id' => $request->class_id,
                'subject_id' => $request->subject_id,
                'academic_year_id' => $request->academic_year_id,
            ],
            [
                'teacher_id' => $teacher->id,
            ]
        );

        $classSubject->load(['class', 'subject', 'academicYear']);

        return $this->success($classSubject, 'Subject assigned to teacher successfully');
    }

    /**
     * Remove class assignment from teacher
     */
    public function removeClass(Request $request, Teacher $teacher, $classId): JsonResponse
    {
        if ($teacher->user->school_id !== $request->get('school_id')) {
            return $this->error('Teacher not found', 404);
        }

        $class = \App\Models\ClassModel::findOrFail($classId);

        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        // Only remove if this teacher is the class teacher
        if ($class->class_teacher_id === $teacher->id) {
            $class->update(['class_teacher_id' => null]);
        }

        return $this->success(null, 'Class assignment removed successfully');
    }

    /**
     * Remove subject assignment from teacher
     */
    public function removeSubject(Request $request, Teacher $teacher, $classSubjectId): JsonResponse
    {
        if ($teacher->user->school_id !== $request->get('school_id')) {
            return $this->error('Teacher not found', 404);
        }

        $classSubject = \App\Models\ClassSubject::findOrFail($classSubjectId);

        // Check if this teacher is assigned to this class subject
        if ($classSubject->teacher_id === $teacher->id) {
            $classSubject->update(['teacher_id' => null]);
        }

        return $this->success(null, 'Subject assignment removed successfully');
    }
}

