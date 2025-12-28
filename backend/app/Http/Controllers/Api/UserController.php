<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends BaseApiController
{
    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $schoolId = $request->get('school_id');
        
        $query = User::query();
        
        // School admins can only see users from their school
        if ($user->isSchoolAdmin() || $user->isAccountsManager()) {
            $query->where('school_id', $schoolId);
        }
        
        // Filter by role
        if ($request->has('role')) {
            $roleName = $request->get('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        $users = $query->with('roles')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));
        
        return $this->paginated($users, 'Users retrieved successfully');
    }
    
    /**
     * Store a newly created user
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        $schoolId = $request->get('school_id');
        
        // Only school admins can create users for their school
        if (!$user->isSchoolAdmin()) {
            return $this->error('Only school administrators can create users', 403);
        }
        
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', 'in:accounts_manager,teacher'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        
        // Create user
        $newUser = User::create([
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'phone' => $request->get('phone'),
            'school_id' => $schoolId,
            'is_active' => $request->get('is_active', true),
        ]);
        
        // Assign role
        $role = Role::where('name', $request->get('role'))->first();
        if ($role) {
            $newUser->roles()->attach($role->id);
        }
        
        // If accounts manager, no additional profile needed
        // If teacher, create teacher profile (handled by TeacherController)
        
        $newUser->load('roles');
        
        return $this->success($newUser, 'User created successfully', 201);
    }
    
    /**
     * Display the specified user
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $authUser = auth()->user();
        $schoolId = $request->get('school_id');
        
        // Ensure user belongs to the same school
        if ($user->school_id !== $schoolId) {
            return $this->error('User not found', 404);
        }
        
        $user->load('roles');
        
        return $this->success($user, 'User retrieved successfully');
    }
    
    /**
     * Update the specified user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $authUser = auth()->user();
        $schoolId = $request->get('school_id');
        
        // Only school admins can update users
        if (!$authUser->isSchoolAdmin()) {
            return $this->error('Only school administrators can update users', 403);
        }
        
        // Ensure user belongs to the same school
        if ($user->school_id !== $schoolId) {
            return $this->error('User not found', 404);
        }
        
        // Prevent updating super admin or other school admins
        if ($user->isSuperAdmin() || ($user->isSchoolAdmin() && $user->id !== $authUser->id)) {
            return $this->error('Cannot update this user', 403);
        }
        
        $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'string', 'in:accounts_manager,teacher'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        
        // Update user
        $updateData = $request->only(['first_name', 'last_name', 'email', 'phone', 'is_active']);
        
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->get('password'));
        }
        
        $user->update($updateData);
        
        // Update role if provided
        if ($request->has('role')) {
            $role = Role::where('name', $request->get('role'))->first();
            if ($role) {
                // Remove all roles and assign new one
                $user->roles()->sync([$role->id]);
            }
        }
        
        $user->load('roles');
        
        return $this->success($user, 'User updated successfully');
    }
    
    /**
     * Remove the specified user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $authUser = auth()->user();
        $schoolId = $request->get('school_id');
        
        // Only school admins can delete users
        if (!$authUser->isSchoolAdmin()) {
            return $this->error('Only school administrators can delete users', 403);
        }
        
        // Ensure user belongs to the same school
        if ($user->school_id !== $schoolId) {
            return $this->error('User not found', 404);
        }
        
        // Prevent deleting super admin, school admin, or self
        if ($user->isSuperAdmin() || $user->isSchoolAdmin() || $user->id === $authUser->id) {
            return $this->error('Cannot delete this user', 403);
        }
        
        $user->delete();
        
        return $this->success(null, 'User deleted successfully');
    }
}

