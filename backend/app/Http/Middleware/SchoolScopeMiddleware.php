<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SchoolScopeMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * This middleware ensures that all queries are scoped to the user's school.
     * Super admins are exempt from this restriction.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Super admin can access all schools
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Allow parents to access parent portal routes without school_id
        // Parents can have children from different schools, so they shouldn't be restricted
        if ($user->isParent() && $request->is('api/parent/*')) {
            return $next($request);
        }

        // Ensure user has a school_id
        if (!$user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'User is not associated with a school',
            ], 403);
        }

        // Add school_id to request for use in controllers
        $request->merge(['school_id' => $user->school_id]);

        return $next($request);
    }
}

