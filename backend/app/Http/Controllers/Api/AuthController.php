<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseApiController
{
    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!$token = auth('api')->attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }

        $user = auth('api')->user();
        $roles = $user->roles()->pluck('name')->toArray();

        return $this->success([
            'user' => $user,
            'token' => $token,
            'roles' => $roles,
        ], 'Login successful');
    }

    /**
     * Register new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'school_id' => $data['school_id'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        // Assign parent role by default (can be changed)
        $parentRole = \App\Models\Role::where('name', 'parent')->first();
        if ($parentRole) {
            $user->roles()->attach($parentRole->id);
            
            // Auto-create Guardian profile for parent users
            \App\Models\Guardian::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $data['phone'] ?? $user->phone ?? null,
                ]
            );
        }

        $token = auth('api')->login($user);
        $roles = $user->roles()->pluck('name')->toArray();

        return $this->success([
            'user' => $user,
            'token' => $token,
            'roles' => $roles,
        ], 'Registration successful', 201);
    }

    /**
     * Logout user
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return $this->success(null, 'Logout successful');
    }

    /**
     * Refresh JWT token
     */
    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();

        return $this->success([
            'token' => $token,
        ], 'Token refreshed');
    }

    /**
     * Get authenticated user
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $roles = $user->roles()->pluck('name')->toArray();

        return $this->success([
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Request password reset
     */
    public function forgotPassword(): JsonResponse
    {
        // TODO: Implement password reset
        return $this->error('Not implemented yet', 501);
    }

    /**
     * Reset password
     */
    public function resetPassword(): JsonResponse
    {
        // TODO: Implement password reset
        return $this->error('Not implemented yet', 501);
    }

    /**
     * Sign in as school admin (Super Admin only)
     * Allows super admin to impersonate a school admin for that school
     */
    public function signInAsSchool(\App\Models\School $school): JsonResponse
    {
        // Only super admin can use this feature
        if (!auth()->user()->isSuperAdmin()) {
            return $this->error('Unauthorized. Only super admins can sign in as school admin.', 403);
        }

        // Find or create school admin user
        $schoolAdmin = \App\Models\User::where('school_id', $school->id)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'school_admin');
            })
            ->first();

        if (!$schoolAdmin) {
            return $this->error('No school admin found for this school. Please create one first.', 404);
        }

        // Generate token for the school admin
        $token = auth('api')->login($schoolAdmin);
        $roles = $schoolAdmin->roles()->pluck('name')->toArray();

        // Log the impersonation (if AuditLog model exists)
        if (class_exists(\App\Models\AuditLog::class)) {
            try {
                \App\Models\AuditLog::create([
                    'school_id' => $school->id,
                    'user_id' => auth()->id(),
                    'action' => 'sign_in_as',
                    'model' => \App\Models\User::class,
                    'model_id' => $schoolAdmin->id,
                    'old_values' => null,
                    'new_values' => ['impersonated_user_id' => $schoolAdmin->id],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Log silently if audit log fails
            }
        }

        return $this->success([
            'user' => $schoolAdmin,
            'token' => $token,
            'roles' => $roles,
            'impersonating' => true,
            'original_user_id' => auth()->id(),
        ], 'Signed in as school admin successfully');
    }
}

