<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Student\StoreStudentRequest;
use App\Http\Requests\Api\Student\UpdateStudentRequest;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\Enrollment;
use App\Models\AcademicYear;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends BaseApiController
{
    /**
     * Display a listing of students
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $request->get('school_id');
        
        $query = Student::where('school_id', $schoolId)
            ->with(['activeEnrollment.class', 'parents.user']);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        // Filter by class
        if ($request->has('class_id')) {
            $query->whereHas('enrollments', function ($q) use ($request) {
                $q->where('class_id', $request->get('class_id'))
                  ->where('status', 'active');
            });
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $students = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Format students for frontend
        $students->getCollection()->transform(function ($student) {
            return [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'full_name' => $student->full_name,
                'date_of_birth' => $student->date_of_birth?->format('Y-m-d'),
                'gender' => $student->gender,
                'email' => $student->email,
                'phone' => $student->phone,
                'address' => $student->address,
                'is_active' => $student->is_active,
                'status' => $student->is_active ? 'Active' : 'Inactive',
                'class' => $student->activeEnrollment?->class?->name ?? 'Not Assigned',
                'class_id' => $student->activeEnrollment?->class_id,
                'active_enrollment' => $student->activeEnrollment,
                'parents' => $student->parents,
            ];
        });

        return $this->paginated($students, 'Students retrieved successfully');
    }

    /**
     * Store a newly created student
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $schoolId = $request->get('school_id');

        DB::beginTransaction();
        try {
            // Always auto-generate student number (multi-tenant requirement)
            // Student numbers are generated based on school code to ensure uniqueness across schools
            $data['student_number'] = Student::generateStudentNumber($schoolId);

            // Create student
            $student = Student::create([
                'school_id' => $schoolId,
                'student_number' => $data['student_number'],
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Create enrollment if class_id is provided
            if (isset($data['class_id']) && $data['class_id']) {
                // Get active academic year for the school
                $academicYear = AcademicYear::where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->first();

                if ($academicYear) {
                    Enrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $data['class_id'],
                        'academic_year_id' => $academicYear->id,
                        'status' => 'active',
                        'enrollment_date' => now(),
                    ]);
                }
            }

            // Handle guardian if provided
            if (isset($data['guardian']) && $data['guardian']['email']) {
                $guardianUser = User::firstOrCreate(
                    ['email' => $data['guardian']['email']],
                    [
                        'school_id' => $schoolId,
                        'first_name' => $data['guardian']['first_name'] ?? 'Guardian',
                        'last_name' => $data['guardian']['last_name'] ?? 'User',
                        'password' => Hash::make('password'), // Default password
                        'is_active' => true,
                    ]
                );

                // Assign parent role (guardian role is stored as 'parent' in database)
                $parentRole = Role::where('name', 'parent')->first();
                if ($parentRole && !$guardianUser->roles->contains($parentRole->id)) {
                    $guardianUser->roles()->attach($parentRole->id);
                }

                // Create or update guardian profile
                $guardianProfile = Guardian::firstOrCreate(
                    ['user_id' => $guardianUser->id],
                    [
                        'phone' => $data['guardian']['phone'] ?? null,
                    ]
                );

                // Attach student to guardian
                $guardianProfile->students()->syncWithoutDetaching([
                    $student->id => ['relationship' => 'guardian', 'is_primary' => true]
                ]);
            }

            DB::commit();

            $student->load(['activeEnrollment.class', 'parents']);

            return $this->success($student, 'Student created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create student: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified student
     */
    public function show(Request $request, Student $student): JsonResponse
    {
        // Ensure student belongs to user's school
        if ($student->school_id !== $request->get('school_id')) {
            return $this->error('Student not found', 404);
        }

        $student->load([
            'parents.user',
            'enrollments.class',
            'enrollments.academicYear',
            'activeEnrollment.class',
            'activeEnrollment.academicYear'
        ]);

        // If user is a parent, check if they have active subscription for this student
        if (auth()->user()->isParent()) {
            $parent = auth()->user()->parent;
            if ($parent) {
                // Check if parent has any active subscription for this student
                $hasActiveSubscription = $parent->subscriptions()
                    ->where('student_id', $student->id)
                    ->where('status', 'active')
                    ->where('expires_at', '>', now())
                    ->exists();
                
                $student->has_active_subscription = $hasActiveSubscription;
            }
        }

        return $this->success($student, 'Student retrieved successfully');
    }

    /**
     * Get student enrollments
     */
    public function enrollments(Request $request, Student $student): JsonResponse
    {
        if ($student->school_id !== $request->get('school_id')) {
            return $this->error('Student not found', 404);
        }

        $enrollments = $student->enrollments()
            ->with(['class', 'academicYear'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($enrollments, 'Enrollments retrieved successfully');
    }

    /**
     * Link guardian to student
     */
    public function linkGuardian(Request $request, Student $student): JsonResponse
    {
        if ($student->school_id !== $request->get('school_id')) {
            return $this->error('Student not found', 404);
        }

        $request->validate([
            'guardian_id' => ['required', 'exists:parents,id'],
            'relationship' => ['nullable', 'string', 'in:father,mother,guardian,other'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $guardian = Guardian::findOrFail($request->guardian_id);

        // Check if already linked
        if ($student->parents()->where('parents.id', $guardian->id)->exists()) {
            return $this->error('Guardian is already linked to this student', 422);
        }

        // If setting as primary, unset other primary guardians
        if ($request->boolean('is_primary')) {
            $existingGuardianIds = $student->parents()->pluck('parents.id')->toArray();
            if (!empty($existingGuardianIds)) {
                foreach ($existingGuardianIds as $existingId) {
                    $student->parents()->updateExistingPivot($existingId, ['is_primary' => false]);
                }
            }
        }

        $student->parents()->attach($guardian->id, [
            'relationship' => $request->get('relationship', 'parent'),
            'is_primary' => $request->boolean('is_primary', false),
        ]);

        $student->load('parents.user');

        return $this->success($student, 'Guardian linked successfully');
    }

    /**
     * Unlink guardian from student
     */
    public function unlinkGuardian(Request $request, Student $student, $guardianId): JsonResponse
    {
        if ($student->school_id !== $request->get('school_id')) {
            return $this->error('Student not found', 404);
        }

        $guardian = Guardian::findOrFail($guardianId);
        $student->parents()->detach($guardian->id);

        return $this->success(null, 'Guardian unlinked successfully');
    }

    /**
     * Update the specified student
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        // Ensure student belongs to user's school
        if ($student->school_id !== $request->get('school_id')) {
            return $this->error('Student not found', 404);
        }

        DB::beginTransaction();
        try {
            $student->update($request->validated());

            // Handle class enrollment update if provided
            if ($request->has('class_id')) {
                $newClassId = $request->get('class_id');
                $currentEnrollment = $student->activeEnrollment;
                $currentClassId = $currentEnrollment?->class_id;

                // Only update if class has changed
                if ($newClassId != $currentClassId) {
                    // Deactivate current enrollment if exists
                    if ($currentEnrollment) {
                        $currentEnrollment->update(['status' => 'inactive']);
                    }

                    // Create new enrollment if new class is provided
                    if ($newClassId) {
                        $academicYear = AcademicYear::where('school_id', $request->get('school_id'))
                            ->where('is_active', true)
                            ->first();

                        if ($academicYear) {
                            Enrollment::create([
                                'student_id' => $student->id,
                                'class_id' => $newClassId,
                                'academic_year_id' => $academicYear->id,
                                'status' => 'active',
                                'enrollment_date' => now(),
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            $student->load(['activeEnrollment.class', 'parents.user']);

            return $this->success($student, 'Student updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update student: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified student
     */
    public function destroy(Request $request, Student $student): JsonResponse
    {
        // Ensure student belongs to user's school
        if ($student->school_id !== $request->get('school_id')) {
            return $this->error('Student not found', 404);
        }

        $student->delete();

        return $this->success(null, 'Student deleted successfully');
    }

    /**
     * Get student results
     */
    public function results(Request $request, Student $student): JsonResponse
    {
        // Ensure student belongs to user's school
        if ($student->school_id !== $request->get('school_id')) {
            return $this->error('Student not found', 404);
        }

        $termId = $request->get('term_id');
        $results = $student->results()
            ->when($termId, function ($q) use ($termId) {
                $q->whereHas('assessment', function ($query) use ($termId) {
                    $query->where('term_id', $termId);
                });
            })
            ->with(['assessment.classSubject.subject', 'assessment.term'])
            ->get();

        return $this->success($results, 'Student results retrieved successfully');
    }

    /**
     * Get student attendance
     */
    public function attendance(Request $request, Student $student): JsonResponse
    {
        // Ensure student belongs to user's school
        if ($student->school_id !== $request->get('school_id')) {
            return $this->error('Student not found', 404);
        }

        $termId = $request->get('term_id');
        $attendance = $student->attendance()
            ->when($termId, function ($q) use ($termId) {
                $q->where('term_id', $termId);
            })
            ->with('term')
            ->orderBy('date', 'desc')
            ->get();

        return $this->success($attendance, 'Student attendance retrieved successfully');
    }
}

