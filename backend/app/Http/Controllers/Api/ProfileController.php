<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends BaseApiController
{
    /**
     * Display the user's profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = auth()->user();
        $user->load(['roles', 'school', 'parent', 'teacher']);

        return $this->success($user, 'Profile retrieved successfully');
    }

    /**
     * Update the user's profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'string'],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);

        // Update basic info
        $user->update($request->only(['first_name', 'last_name', 'phone', 'avatar']));

        // Update password if provided
        if ($request->has('password')) {
            if (!Hash::check($request->get('current_password'), $user->password)) {
                return $this->error('Current password is incorrect', 422);
            }

            $user->password = Hash::make($request->get('password'));
            $user->save();
        }

        $user->load(['roles', 'school']);

        return $this->success($user, 'Profile updated successfully');
    }
}

