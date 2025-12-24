<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Subscription;

class ParentSubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * This middleware enforces that parents can only access data for terms
     * they have paid subscriptions for.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Only apply to parents
        if (!$user || !$user->isParent()) {
            return $next($request);
        }

        // Get term_id from request (route parameter or request body)
        $termId = $request->route('term_id') 
            ?? $request->input('term_id')
            ?? $request->query('term_id');

        // Get student_id from request
        $studentId = $request->route('student_id')
            ?? $request->input('student_id')
            ?? $request->query('student_id');

        // If no term_id or student_id, allow (might be a general endpoint)
        if (!$termId || !$studentId) {
            return $next($request);
        }

        // Check if parent has active subscription for this student and term
        $parent = $user->parent;
        
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent profile not found',
            ], 404);
        }

        // Check subscription
        $hasSubscription = $parent->hasActiveSubscription($studentId, $termId);

        if (!$hasSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription required. You need to pay for this term to access student data.',
                'requires_payment' => true,
                'student_id' => $studentId,
                'term_id' => $termId,
            ], 403);
        }

        return $next($request);
    }
}

