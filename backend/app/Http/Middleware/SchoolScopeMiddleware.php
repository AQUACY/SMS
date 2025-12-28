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

        // Allow parents to access certain routes without school_id
        // Parents can have children from different schools, so they shouldn't be restricted
        if ($user->isParent()) {
            // Allow parent portal routes
            if ($request->is('api/parent/*')) {
                return $next($request);
            }
            
            // Allow auth endpoints (me, refresh, logout)
            if ($request->is('api/auth/*')) {
                return $next($request);
            }
            
            // Allow profile endpoints
            if ($request->is('api/profile*')) {
                return $next($request);
            }
            
            // Allow terms endpoint (parents need to see terms for payments)
            if ($request->is('api/terms*')) {
                return $next($request);
            }
            
            // Allow subscription prices endpoint (parents need to see subscription prices)
            if ($request->is('api/subscription-prices*')) {
                return $next($request);
            }
            
            // Allow fees endpoint (parents need to check fees for their children)
            if ($request->is('api/fees*')) {
                return $next($request);
            }
            
            // Allow attendance endpoint (parents need to view their children's attendance)
            if ($request->is('api/attendance/*')) {
                return $next($request);
            }
            
            // Allow results endpoint (parents need to view their children's results)
            if ($request->is('api/results/*')) {
                return $next($request);
            }
            
            // Allow report cards endpoint (parents need to view their children's report cards)
            if ($request->is('api/report-cards/*')) {
                return $next($request);
            }
            
            // Allow assessments endpoint (parents need to view their children's assessments)
            if ($request->is('api/assessments*')) {
                return $next($request);
            }
            
            // Allow dashboard endpoint (parents need to see their dashboard)
            if ($request->is('api/dashboard*')) {
                return $next($request);
            }
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

