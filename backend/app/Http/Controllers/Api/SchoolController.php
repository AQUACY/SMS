<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\School\StoreSchoolRequest;
use App\Http\Requests\Api\School\UpdateSchoolRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolController extends BaseApiController
{
    /**
     * Display a listing of schools (Super Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        // Only super admin can see all schools
        if (!auth()->user()->isSuperAdmin()) {
            return $this->error('Unauthorized. Only super admins can view all schools.', 403);
        }

        $query = School::withCount(['users', 'students', 'academicYears']);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $schools = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($schools, 'Schools retrieved successfully');
    }

    /**
     * Store a newly created school
     */
    public function store(StoreSchoolRequest $request): JsonResponse
    {
        // Only super admin can create schools
        if (!auth()->user()->isSuperAdmin()) {
            return $this->error('Unauthorized. Only super admins can create schools.', 403);
        }

        $data = $request->validated();
        $school = School::create($data);

        // Create school admin user if provided
        if ($request->has('admin_email') && $request->has('admin_password')) {
            $user = \App\Models\User::create([
                'school_id' => $school->id,
                'first_name' => $request->get('admin_first_name', 'Admin'),
                'last_name' => $request->get('admin_last_name', 'User'),
                'email' => $request->get('admin_email'),
                'password' => \Illuminate\Support\Facades\Hash::make($request->get('admin_password')),
                'is_active' => true,
            ]);

            // Assign school admin role
            $schoolAdminRole = \App\Models\Role::where('name', 'school_admin')->first();
            if ($schoolAdminRole) {
                $user->roles()->attach($schoolAdminRole->id);
            }
        }

        $school->loadCount(['users', 'students', 'academicYears']);

        return $this->success($school, 'School created successfully', 201);
    }

    /**
     * Display the specified school
     */
    public function show(Request $request, School $school): JsonResponse
    {
        // Super admin can see any school, others can only see their own
        if (!auth()->user()->isSuperAdmin() && auth()->user()->school_id !== $school->id) {
            return $this->error('Unauthorized', 403);
        }

        $school->loadCount(['users', 'students', 'academicYears']);
        $school->load(['academicYears', 'users' => function ($q) {
            $q->whereHas('roles', function ($query) {
                $query->where('name', 'school_admin');
            })->limit(5);
        }]);

        return $this->success($school, 'School retrieved successfully');
    }

    /**
     * Update the specified school
     */
    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        // Super admin can update any school, school admin can only update their own
        if (!auth()->user()->isSuperAdmin() && auth()->user()->school_id !== $school->id) {
            return $this->error('Unauthorized', 403);
        }

        $school->update($request->validated());
        $school->loadCount(['users', 'students', 'academicYears']);

        return $this->success($school, 'School updated successfully');
    }

    /**
     * Remove the specified school (soft delete)
     */
    public function destroy(Request $request, School $school): JsonResponse
    {
        // Only super admin can delete schools
        if (!auth()->user()->isSuperAdmin()) {
            return $this->error('Unauthorized. Only super admins can delete schools.', 403);
        }

        $school->delete();

        return $this->success(null, 'School deleted successfully');
    }

    /**
     * Get school statistics
     */
    public function statistics(Request $request, School $school): JsonResponse
    {
        // Super admin can see any school stats, others can only see their own
        if (!auth()->user()->isSuperAdmin() && auth()->user()->school_id !== $school->id) {
            return $this->error('Unauthorized', 403);
        }

        $stats = [
            'total_users' => $school->users()->count(),
            'total_students' => $school->students()->count(),
            'total_teachers' => $school->users()->whereHas('roles', function ($q) {
                $q->where('name', 'teacher');
            })->count(),
            'total_classes' => $school->classes()->count(),
            'total_subjects' => $school->subjects()->count(),
            'active_academic_years' => $school->academicYears()->where('is_active', true)->count(),
            'total_academic_years' => $school->academicYears()->count(),
        ];

        return $this->success($stats, 'School statistics retrieved successfully');
    }

    /**
     * Activate/Deactivate school
     */
    public function toggleStatus(Request $request, School $school): JsonResponse
    {
        // Only super admin can toggle school status
        if (!auth()->user()->isSuperAdmin()) {
            return $this->error('Unauthorized. Only super admins can change school status.', 403);
        }

        $school->is_active = !$school->is_active;
        $school->save();

        return $this->success($school, "School " . ($school->is_active ? 'activated' : 'deactivated') . " successfully");
    }
}

