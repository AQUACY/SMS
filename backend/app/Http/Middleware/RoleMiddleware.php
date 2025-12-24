<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user = auth()->user();

        // Super admin has access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Handle comma-separated roles (e.g., "super_admin,school_admin,teacher")
        // Laravel passes route parameters as separate arguments, but if it's a single string,
        // we need to split it
        $roleArray = [];
        foreach ($roles as $role) {
            if (strpos($role, ',') !== false) {
                // Split comma-separated roles
                $roleArray = array_merge($roleArray, array_map('trim', explode(',', $role)));
            } else {
                $roleArray[] = trim($role);
            }
        }
        $roleArray = array_unique($roleArray);

        // Check if user has any of the required roles
        if (!$user->hasAnyRole($roleArray)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions. Required roles: ' . implode(', ', $roleArray),
            ], 403);
        }

        return $next($request);
    }
}

