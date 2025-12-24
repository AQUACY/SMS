<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Class\StoreClassRequest;
use App\Http\Requests\Api\Class\UpdateClassRequest;
use App\Http\Requests\Api\Student\StoreStudentRequest;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClassController extends BaseApiController
{
    /**
     * Display a listing of classes
     */
    public function index(Request $request): JsonResponse
    {
        $query = ClassModel::where('school_id', $request->get('school_id'))
            ->with(['classTeacher.user', 'academicYear']);

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->get('academic_year_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $classes = $query->paginate($request->get('per_page', 15));

        return $this->paginated($classes, 'Classes retrieved successfully');
    }

    /**
     * Store a newly created class
     */
    public function store(StoreClassRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['school_id'] = $request->get('school_id');

        $class = ClassModel::create($data);
        $class->load(['classTeacher.user', 'academicYear']);

        return $this->success($class, 'Class created successfully', 201);
    }

    /**
     * Display the specified class
     */
    public function show(Request $request, ClassModel $class): JsonResponse
    {
        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        $class->load(['classTeacher.user', 'academicYear', 'students', 'subjects']);

        return $this->success($class, 'Class retrieved successfully');
    }

    /**
     * Update the specified class
     */
    public function update(UpdateClassRequest $request, ClassModel $class): JsonResponse
    {
        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        $class->update($request->validated());
        $class->load(['classTeacher.user', 'academicYear']);

        return $this->success($class, 'Class updated successfully');
    }

    /**
     * Remove the specified class
     */
    public function destroy(Request $request, ClassModel $class): JsonResponse
    {
        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        $class->delete();

        return $this->success(null, 'Class deleted successfully');
    }

    /**
     * Get class students
     */
    public function students(Request $request, ClassModel $class): JsonResponse
    {
        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        $students = $class->students()->with('parents.user')->get();

        return $this->success($students, 'Class students retrieved successfully');
    }

    /**
     * Get class subjects
     */
    public function subjects(Request $request, ClassModel $class): JsonResponse
    {
        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        $subjects = $class->classSubjects()->with(['subject', 'teacher.user', 'academicYear'])->get();

        return $this->success($subjects, 'Class subjects retrieved successfully');
    }

    /**
     * Add a new student to the class (create and enroll)
     */
    public function addStudent(StoreStudentRequest $request, ClassModel $class): JsonResponse
    {
        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        if (!$class->academic_year_id) {
            return $this->error('Class does not have an academic year assigned', 422);
        }

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['school_id'] = $request->get('school_id');

            // Auto-generate student number if not provided
            if (empty($data['student_number'])) {
                $data['student_number'] = Student::generateStudentNumber($data['school_id']);
            } else {
                // If provided, ensure it doesn't already exist
                $existingStudent = Student::where('student_number', $data['student_number'])->first();
                if ($existingStudent) {
                    DB::rollBack();
                    return $this->error('Student number already exists. Leave it blank to auto-generate.', 422);
                }
            }

            // Create student
            $student = Student::create($data);

            // Handle guardian creation/linking if provided
            if ($request->has('guardian')) {
                $guardianData = $request->input('guardian');
                
                if (isset($guardianData['email'])) {
                    // Check if guardian user exists
                    $guardianUser = User::where('email', $guardianData['email'])
                        ->where('school_id', $data['school_id'])
                        ->first();

                    if (!$guardianUser) {
                        // Create guardian user
                        $guardianUser = User::create([
                            'school_id' => $data['school_id'],
                            'first_name' => $guardianData['first_name'] ?? '',
                            'last_name' => $guardianData['last_name'] ?? '',
                            'email' => $guardianData['email'],
                            'password' => Hash::make($guardianData['password'] ?? 'password123'),
                        ]);

                        // Assign guardian role
                        $guardianRole = Role::where('name', 'guardian')->first();
                        if ($guardianRole) {
                            $guardianUser->roles()->attach($guardianRole->id);
                        }
                    }

                    // Create or get guardian record
                    $guardian = Guardian::firstOrCreate(
                        ['user_id' => $guardianUser->id],
                        [
                            'relationship' => $guardianData['relationship'] ?? 'parent',
                            'is_primary' => $guardianData['is_primary'] ?? false,
                        ]
                    );

                    // Link student to guardian
                    $student->parents()->syncWithoutDetaching([$guardian->id => [
                        'relationship' => $guardian->relationship,
                        'is_primary' => $guardian->is_primary,
                    ]]);
                }
            }

            // Create enrollment
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'academic_year_id' => $class->academic_year_id,
                'enrollment_date' => now()->toDateString(),
                'status' => 'active',
            ]);

            DB::commit();

            $student->load(['activeEnrollment.class', 'parents.user']);

            return $this->success($student, 'Student created and enrolled successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to add student: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign a subject to the class
     */
    public function assignSubject(Request $request, ClassModel $class): JsonResponse
    {
        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        if (!$class->academic_year_id) {
            return $this->error('Class does not have an academic year assigned', 422);
        }

        $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
        ]);

        $subject = \App\Models\Subject::findOrFail($request->subject_id);

        // Check if subject belongs to same school
        if ($subject->school_id !== $request->get('school_id')) {
            return $this->error('Subject not found', 404);
        }

        // Check if teacher belongs to same school (if provided)
        if ($request->has('teacher_id') && $request->teacher_id) {
            $teacher = \App\Models\Teacher::findOrFail($request->teacher_id);
            if ($teacher->user->school_id !== $request->get('school_id')) {
                return $this->error('Teacher not found', 404);
            }
        }

        // Create or update class subject assignment
        $classSubject = \App\Models\ClassSubject::updateOrCreate(
            [
                'class_id' => $class->id,
                'subject_id' => $request->subject_id,
                'academic_year_id' => $class->academic_year_id,
            ],
            [
                'teacher_id' => $request->teacher_id ?? null,
            ]
        );

        $classSubject->load(['subject', 'teacher.user', 'academicYear']);

        return $this->success($classSubject, 'Subject assigned to class successfully', 201);
    }

    /**
     * Remove a subject from the class
     */
    public function removeSubject(Request $request, ClassModel $class, $classSubjectId): JsonResponse
    {
        if ($class->school_id !== $request->get('school_id')) {
            return $this->error('Class not found', 404);
        }

        $classSubject = \App\Models\ClassSubject::findOrFail($classSubjectId);

        // Check if this class subject belongs to this class
        if ($classSubject->class_id !== $class->id) {
            return $this->error('Class subject not found', 404);
        }

        $classSubject->delete();

        return $this->success(null, 'Subject removed from class successfully');
    }
}

