<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentController extends BaseApiController
{
    /**
     * Get parent's children
     */
    public function children(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Auto-create parent profile if it doesn't exist
        $parent = $user->parent;
        if (!$parent) {
            $parent = \App\Models\Guardian::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $user->phone ?? null,
                ]
            );
        }

        $children = $parent->students()
            ->with(['activeEnrollment.class', 'school'])
            ->get();

        // Add subscription status and full_name for each child
        $children->each(function ($child) use ($parent) {
            $hasActiveSubscription = $parent->subscriptions()
                ->where('student_id', $child->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->exists();
            
            $child->has_active_subscription = $hasActiveSubscription;
            
            // Add full_name attribute
            $child->full_name = trim("{$child->first_name} {$child->middle_name} {$child->last_name}");
        });

        return $this->success($children, 'Children retrieved successfully');
    }

    /**
     * Get parent's subscriptions
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Auto-create parent profile if it doesn't exist
        $parent = $user->parent;
        if (!$parent) {
            $parent = \App\Models\Guardian::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $user->phone ?? null,
                ]
            );
        }

        $subscriptions = $parent->subscriptions()
            ->with(['student', 'term.academicYear', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($subscriptions, 'Subscriptions retrieved successfully');
    }

    /**
     * Get parent's payments
     */
    public function payments(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Auto-create parent profile if it doesn't exist
        $parent = $user->parent;
        if (!$parent) {
            $parent = \App\Models\Guardian::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $user->phone ?? null,
                ]
            );
        }

        $payments = $parent->payments()
            ->with(['student', 'term'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($payments, 'Payments retrieved successfully');
    }

    /**
     * Link a child by student ID or student number
     * Supports formats: "BA01-STU001" (school code + student number) or just "STU001"
     */
    public function linkChild(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Auto-create parent profile if it doesn't exist
        $parent = $user->parent;
        if (!$parent) {
            // Create Guardian profile for the user
            $parent = \App\Models\Guardian::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $user->phone ?? null,
                ]
            );
        }

        $request->validate([
            'student_identifier' => ['required', 'string'],
            'relationship' => ['nullable', 'string', 'in:father,mother,guardian,other'],
        ]);

        $studentIdentifier = trim($request->get('student_identifier'));
        $student = null;
        $school = null;

        // Check if the identifier contains a hyphen (format: SCHOOLCODE-STUNUMBER)
        if (strpos($studentIdentifier, '-') !== false) {
            // Parse format: "B12-STU001"
            $parts = explode('-', $studentIdentifier, 2);
            if (count($parts) === 2) {
                $schoolCode = trim($parts[0]);
                $studentNumberPart = trim($parts[1]);
                $fullStudentNumber = $studentIdentifier; // "B12-STU001"

                // Find school by code (case-insensitive)
                $school = \App\Models\School::whereRaw('UPPER(code) = ?', [strtoupper($schoolCode)])->first();
                
                if (!$school) {
                    return $this->error('School not found with code: ' . $schoolCode . '. Please check the Student Number provided by the school.', 404);
                }

                // Try to find student by full student_number first (as it's stored in DB)
                // Search case-insensitively to handle any case variations
                $student = \App\Models\Student::where('school_id', $school->id)
                    ->whereRaw('UPPER(student_number) = ?', [strtoupper($fullStudentNumber)])
                    ->first();

                // If not found, try searching by just the number part (backward compatibility)
                if (!$student) {
                    $student = \App\Models\Student::where('school_id', $school->id)
                        ->where(function ($query) use ($studentNumberPart) {
                            $query->whereRaw('UPPER(student_number) = ?', [strtoupper($studentNumberPart)])
                                  ->orWhere('student_number', 'like', '%' . $studentNumberPart);
                        })
                        ->first();
                }
            }
        } else {
            // Try to find by student_number across all schools (fallback)
            // Or if parent has school_id, search in that school
            $user = auth()->user();
            if ($user->school_id) {
                $student = \App\Models\Student::where('school_id', $user->school_id)
                    ->where('student_number', $studentIdentifier)
                    ->first();
            } else {
                // Search across all schools (less secure, but allows linking)
                $student = \App\Models\Student::where('student_number', $studentIdentifier)
                    ->first();
            }
        }

        if (!$student) {
            return $this->error('Student not found. Please check the Student Number provided by the school. Format: SCHOOLCODE-STUNUMBER (e.g., BA01-STU001)', 404);
        }

        // Check if already linked
        if ($parent->students()->where('students.id', $student->id)->exists()) {
            return $this->error('This child is already linked to your account', 422);
        }

        // Link the student
        $parent->students()->attach($student->id, [
            'relationship' => $request->get('relationship', 'parent'),
            'is_primary' => $parent->students()->count() === 0, // First child is primary
        ]);

        $student->load(['activeEnrollment.class', 'school']);

        return $this->success($student, 'Child linked successfully');
    }
}

