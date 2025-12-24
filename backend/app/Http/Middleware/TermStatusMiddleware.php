<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Term;

class TermStatusMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * This middleware prevents creating new assessments when term is in
     * 'closing', 'closed', or 'archived' status.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get term_id from request
        $termId = $request->route('term_id')
            ?? $request->input('term_id')
            ?? $request->query('term_id');

        if (!$termId) {
            return $next($request);
        }

        $term = Term::find($termId);

        if (!$term) {
            return response()->json([
                'success' => false,
                'message' => 'Term not found',
            ], 404);
        }

        // Check if term allows new assessments
        if (!$term->allowsNewAssessments()) {
            return response()->json([
                'success' => false,
                'message' => "Cannot create assessments. Term status is '{$term->status}'. Only 'draft' and 'active' terms allow new assessments.",
                'term_status' => $term->status,
            ], 403);
        }

        return $next($request);
    }
}

