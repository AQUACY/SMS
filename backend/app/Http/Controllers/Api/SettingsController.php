<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends BaseApiController
{
    /**
     * Display school settings
     */
    public function index(Request $request): JsonResponse
    {
        $school = auth()->user()->school;
        
        if (!$school) {
            return $this->error('School not found', 404);
        }

        return $this->success($school, 'Settings retrieved successfully');
    }

    /**
     * Update school settings
     */
    public function update(Request $request): JsonResponse
    {
        $school = auth()->user()->school;
        
        if (!$school) {
            return $this->error('School not found', 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $school->update($request->only(['name', 'logo', 'address', 'phone', 'email']));

        return $this->success($school, 'Settings updated successfully');
    }
}

