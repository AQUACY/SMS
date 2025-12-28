<?php

namespace App\Http\Controllers\Api;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Result;
use App\Models\Term;
use App\Models\Guardian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends BaseApiController
{
    /**
     * Get dashboard statistics based on user role
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $this->superAdminStats();
        } elseif ($user->isSchoolAdmin()) {
            return $this->schoolAdminStats($request);
        } elseif ($user->isAccountsManager()) {
            return $this->accountsManagerStats($request);
        } elseif ($user->isTeacher()) {
            return $this->teacherStats($request);
        } elseif ($user->isParent()) {
            return $this->parentStats($request);
        }

        return $this->error('Unauthorized', 403);
    }

    /**
     * Super Admin Dashboard Statistics
     */
    private function superAdminStats(): JsonResponse
    {
        $stats = [
            'total_schools' => School::count(),
            'active_schools' => School::where('is_active', true)->count(),
            'total_users' => User::count(),
            'total_students' => Student::count(),
            'total_teachers' => User::whereHas('roles', function ($q) {
                $q->where('name', 'teacher');
            })->count(),
            'total_parents' => User::whereHas('roles', function ($q) {
                $q->where('name', 'parent');
            })->count(),
            'total_subscription_payments' => Payment::where('payment_type', 'subscription_payment')->count(),
            'total_subscription_revenue' => Payment::where('payment_type', 'subscription_payment')
                ->where('status', 'completed')
                ->sum('amount'),
            'pending_subscription_payments' => Payment::where('payment_type', 'subscription_payment')
                ->whereIn('status', ['pending', 'processing'])
                ->count(),
            'recent_schools' => School::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'code', 'is_active', 'created_at']),
        ];

        // Monthly subscription revenue (last 6 months)
        $monthlyRevenue = Payment::where('payment_type', 'subscription_payment')
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $stats['monthly_revenue'] = $monthlyRevenue;

        return $this->success($stats, 'Super admin dashboard statistics retrieved successfully');
    }

    /**
     * School Admin Dashboard Statistics
     */
    private function schoolAdminStats(Request $request): JsonResponse
    {
        $schoolId = $request->get('school_id');
        if (!$schoolId) {
            return $this->error('School ID is required', 400);
        }

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->school_id !== (int)$schoolId) {
            return $this->error('Unauthorized', 403);
        }

        // Get active term
        $activeTerm = Term::whereHas('academicYear', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId)->where('is_active', true);
        })->where('status', 'active')->first();

        $stats = [
            'total_students' => Student::where('school_id', $schoolId)->count(),
            'active_students' => Student::where('school_id', $schoolId)->where('is_active', true)->count(),
            'total_teachers' => User::where('school_id', $schoolId)
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'teacher');
                })->count(),
            'total_classes' => ClassModel::where('school_id', $schoolId)->count(),
            'active_classes' => ClassModel::where('school_id', $schoolId)->where('is_active', true)->count(),
            'total_subjects' => DB::table('subjects')->where('school_id', $schoolId)->count(),
            'total_fee_payments' => Payment::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('payment_type', 'fee_payment')->count(),
            'total_fee_revenue' => Payment::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('payment_type', 'fee_payment')
                ->where('status', 'completed')
                ->sum('amount'),
            'pending_fee_payments' => Payment::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('payment_type', 'fee_payment')
                ->whereIn('status', ['pending', 'processing'])
                ->count(),
            'active_term' => $activeTerm ? [
                'id' => $activeTerm->id,
                'name' => $activeTerm->name,
                'term_number' => $activeTerm->term_number,
            ] : null,
        ];

        // Today's attendance stats
        if ($activeTerm) {
            $todayAttendance = Attendance::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
                ->where('term_id', $activeTerm->id)
                ->whereDate('date', Carbon::today())
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present'),
                    DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
                    DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late')
                )
                ->first();

            $stats['today_attendance'] = [
                'total' => $todayAttendance->total ?? 0,
                'present' => $todayAttendance->present ?? 0,
                'absent' => $todayAttendance->absent ?? 0,
                'late' => $todayAttendance->late ?? 0,
            ];
        }

        // Recent fee payments
        $recentPayments = Payment::whereHas('student', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })
            ->where('payment_type', 'fee_payment')
            ->with(['student', 'parent.user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $stats['recent_payments'] = $recentPayments;

        // Pending assessments (not finalized)
        if ($activeTerm) {
            $pendingAssessments = Assessment::whereHas('classSubject.class', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
                ->where('term_id', $activeTerm->id)
                ->where('is_finalized', false)
                ->count();

            $stats['pending_assessments'] = $pendingAssessments;
        }

        return $this->success($stats, 'School admin dashboard statistics retrieved successfully');
    }

    /**
     * Accounts Manager Dashboard Statistics
     */
    private function accountsManagerStats(Request $request): JsonResponse
    {
        $schoolId = $request->get('school_id');
        if (!$schoolId) {
            return $this->error('School ID is required', 400);
        }

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->school_id !== (int)$schoolId) {
            return $this->error('Unauthorized', 403);
        }

        // Get active term
        $activeTerm = Term::whereHas('academicYear', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId)->where('is_active', true);
        })->where('status', 'active')->first();

        $stats = [
            'total_fee_payments' => Payment::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('payment_type', 'fee_payment')->count(),
            'completed_payments' => Payment::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('payment_type', 'fee_payment')
                ->where('status', 'completed')
                ->count(),
            'pending_payments' => Payment::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('payment_type', 'fee_payment')
                ->whereIn('status', ['pending', 'processing'])
                ->count(),
            'failed_payments' => Payment::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('payment_type', 'fee_payment')
                ->where('status', 'failed')
                ->count(),
            'total_revenue' => Payment::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('payment_type', 'fee_payment')
                ->where('status', 'completed')
                ->sum('amount'),
            'monthly_revenue' => Payment::whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('payment_type', 'fee_payment')
                ->where('status', 'completed')
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->sum('amount'),
            'active_term' => $activeTerm ? [
                'id' => $activeTerm->id,
                'name' => $activeTerm->name,
            ] : null,
        ];

        // Recent pending payments
        $recentPending = Payment::whereHas('student', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })
            ->where('payment_type', 'fee_payment')
            ->whereIn('status', ['pending', 'processing'])
            ->with(['student', 'parent.user', 'term'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $stats['recent_pending_payments'] = $recentPending;

        // Payment status breakdown
        $statusBreakdown = Payment::whereHas('student', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })
            ->where('payment_type', 'fee_payment')
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('status')
            ->get();

        $stats['status_breakdown'] = $statusBreakdown;

        // Monthly revenue (last 6 months)
        $monthlyRevenue = Payment::whereHas('student', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })
            ->where('payment_type', 'fee_payment')
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $stats['monthly_revenue_chart'] = $monthlyRevenue;

        return $this->success($stats, 'Accounts manager dashboard statistics retrieved successfully');
    }

    /**
     * Teacher Dashboard Statistics
     */
    private function teacherStats(Request $request): JsonResponse
    {
        $schoolId = $request->get('school_id');
        if (!$schoolId) {
            return $this->error('School ID is required', 400);
        }

        $user = auth()->user();
        $teacher = $user->teacher;

        if (!$teacher) {
            return $this->error('Teacher profile not found', 404);
        }

        // Get active term
        $activeTerm = Term::whereHas('academicYear', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId)->where('is_active', true);
        })->where('status', 'active')->first();

        // Get assigned classes
        $assignedClasses = ClassModel::where('school_id', $schoolId)
            ->where(function ($q) use ($teacher) {
                $q->where('class_teacher_id', $teacher->id)
                    ->orWhereHas('subjects', function ($query) use ($teacher) {
                        $query->where('teacher_id', $teacher->id);
                    });
            })
            ->where('is_active', true)
            ->with(['academicYear', 'classTeacher.user'])
            ->get();

        // Get total students in assigned classes
        $totalStudents = 0;
        foreach ($assignedClasses as $class) {
            $totalStudents += $class->students()->count();
        }

        $stats = [
            'assigned_classes' => $assignedClasses->count(),
            'total_students' => $totalStudents,
            'active_term' => $activeTerm ? [
                'id' => $activeTerm->id,
                'name' => $activeTerm->name,
            ] : null,
        ];

        // Get assessments for this term
        if ($activeTerm) {
            $assessments = Assessment::where('teacher_id', $teacher->id)
                ->where('term_id', $activeTerm->id)
                ->with(['classSubject.class', 'classSubject.subject'])
                ->get();

            $stats['total_assessments'] = $assessments->count();
            $stats['pending_assessments'] = $assessments->where('is_finalized', false)->count();
            $stats['finalized_assessments'] = $assessments->where('is_finalized', true)->count();

            // Today's attendance for assigned classes
            $todayAttendance = Attendance::whereIn('class_id', $assignedClasses->pluck('id'))
                ->where('term_id', $activeTerm->id)
                ->whereDate('date', Carbon::today())
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present'),
                    DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent')
                )
                ->first();

            $stats['today_attendance'] = [
                'total' => $todayAttendance->total ?? 0,
                'present' => $todayAttendance->present ?? 0,
                'absent' => $todayAttendance->absent ?? 0,
            ];

            // Recent assessments
            $recentAssessments = Assessment::where('teacher_id', $teacher->id)
                ->where('term_id', $activeTerm->id)
                ->with(['classSubject.class', 'classSubject.subject'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $stats['recent_assessments'] = $recentAssessments;
        }

        $stats['assigned_classes_list'] = $assignedClasses->map(function ($class) {
            return [
                'id' => $class->id,
                'name' => $class->name,
                'level' => $class->level,
                'student_count' => $class->students()->count(),
            ];
        });

        return $this->success($stats, 'Teacher dashboard statistics retrieved successfully');
    }

    /**
     * Parent Dashboard Statistics
     */
    private function parentStats(Request $request): JsonResponse
    {
        $user = auth()->user();
        $parent = $user->parent;

        if (!$parent) {
            return $this->error('Parent profile not found', 404);
        }

        // Get linked students
        $students = $parent->students()->with(['school', 'activeEnrollment.class'])->get();

        $stats = [
            'total_children' => $students->count(),
            'children' => $students->map(function ($student) {
                $activeEnrollment = $student->activeEnrollment;
                return [
                    'id' => $student->id,
                    'name' => $student->full_name ?? trim("{$student->first_name} {$student->last_name}"),
                    'student_number' => $student->student_number ?? 'N/A',
                    'school' => $student->school ? $student->school->name : null,
                    'class' => ($activeEnrollment && $activeEnrollment->class) ? $activeEnrollment->class->name : null,
                ];
            })->values(),
        ];

        // Get active subscriptions
        $activeSubscriptions = Subscription::whereHas('student', function ($q) use ($parent) {
            $q->whereHas('parents', function ($query) use ($parent) {
                $query->where('parent_id', $parent->id);
            });
        })
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->with(['student', 'term'])
            ->get();

        $stats['active_subscriptions'] = $activeSubscriptions->count();
        $stats['subscriptions'] = $activeSubscriptions->map(function ($sub) {
            return [
                'id' => $sub->id,
                'student_name' => $sub->student ? ($sub->student->full_name ?? trim("{$sub->student->first_name} {$sub->student->last_name}")) : 'N/A',
                'term_name' => $sub->term ? $sub->term->name : 'N/A',
                'expires_at' => $sub->expires_at,
            ];
        })->values();

        // Get recent payments
        $recentPayments = Payment::where('parent_id', $parent->id)
            ->where('payment_type', 'subscription_payment')
            ->with(['student', 'term'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $stats['recent_payments'] = $recentPayments;

        // Get total spent
        $totalSpent = Payment::where('parent_id', $parent->id)
            ->where('payment_type', 'subscription_payment')
            ->where('status', 'completed')
            ->sum('amount');

        $stats['total_spent'] = $totalSpent;

        // For each student, get their latest activity
        $studentActivity = [];
        foreach ($students as $student) {
            // Get latest attendance
            $latestAttendance = Attendance::where('student_id', $student->id)
                ->orderBy('date', 'desc')
                ->first();

            // Get latest result
            $latestResult = Result::where('student_id', $student->id)
                ->with('assessment')
                ->orderBy('created_at', 'desc')
                ->first();

            $studentActivity[] = [
                'student_id' => $student->id,
                'student_name' => $student->full_name ?? trim("{$student->first_name} {$student->last_name}"),
                'latest_attendance' => $latestAttendance ? [
                    'date' => $latestAttendance->date ? $latestAttendance->date->format('Y-m-d') : null,
                    'status' => $latestAttendance->status ?? 'N/A',
                ] : null,
                'latest_result' => $latestResult ? [
                    'assessment_name' => ($latestResult->assessment && $latestResult->assessment->name) ? $latestResult->assessment->name : 'N/A',
                    'marks_obtained' => $latestResult->marks_obtained ?? 0,
                    'total_marks' => $latestResult->total_marks ?? 0,
                ] : null,
            ];
        }

        $stats['student_activity'] = $studentActivity;

        return $this->success($stats, 'Parent dashboard statistics retrieved successfully');
    }
}

