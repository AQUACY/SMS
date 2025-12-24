<?php

namespace App\Http\Controllers\Api;

use App\Models\Guardian;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GuardianController extends BaseApiController
{
    /**
     * Display a listing of guardians
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $request->get('school_id');

        $query = Guardian::whereHas('user', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->with('user');

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $guardians = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return $this->paginated($guardians, 'Guardians retrieved successfully');
    }

    /**
     * Store a newly created guardian
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $schoolId = $request->get('school_id');

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'school_id' => $schoolId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password ?? 'password'),
                'is_active' => true,
            ]);

            // Assign parent role
            $parentRole = Role::where('name', 'parent')->first();
            if ($parentRole) {
                $user->roles()->attach($parentRole->id);
            }

            // Create guardian profile
            $guardian = Guardian::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
            ]);

            $guardian->load('user');

            DB::commit();

            return $this->success($guardian, 'Guardian created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create guardian: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified guardian
     */
    public function show(Request $request, Guardian $guardian): JsonResponse
    {
        $schoolId = $request->get('school_id');

        if ($guardian->user->school_id !== $schoolId) {
            return $this->error('Guardian not found', 404);
        }

        $guardian->load(['user', 'students.activeEnrollment.class']);

        return $this->success($guardian, 'Guardian retrieved successfully');
    }
}

