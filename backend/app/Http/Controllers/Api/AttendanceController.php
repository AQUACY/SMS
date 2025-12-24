<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Attendance\MarkAttendanceRequest;
use App\Http\Requests\Api\Attendance\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Term;
use App\Models\ClassModel;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Tymon\JWTAuth\Facades\JWTAuth;

class AttendanceController extends BaseApiController
{
    /**
     * Display a listing of attendance records
     */
    public function index(Request $request): JsonResponse
    {
        $query = Attendance::whereHas('term', function ($q) use ($request) {
            $q->whereHas('academicYear', function ($query) use ($request) {
                $query->where('school_id', $request->get('school_id'));
            });
        })->with(['student', 'class', 'term', 'markedBy']);

        if ($request->has('term_id')) {
            $query->where('term_id', $request->get('term_id'));
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->get('student_id'));
        }

        if ($request->has('date')) {
            $query->where('date', $request->get('date'));
        }

        $attendance = $query->orderBy('date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($attendance, 'Attendance records retrieved successfully');
    }

    /**
     * Mark attendance for students
     */
    public function mark(MarkAttendanceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $term = Term::findOrFail($data['term_id']);

        // Ensure term belongs to user's school
        if ($term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Term not found', 404);
        }

        $attendanceRecords = [];

        foreach ($data['students'] as $studentData) {
            $attendanceRecords[] = Attendance::updateOrCreate(
                [
                    'term_id' => $data['term_id'],
                    'class_id' => $data['class_id'],
                    'student_id' => $studentData['student_id'],
                    'date' => $data['date'],
                ],
                [
                    'status' => $studentData['status'],
                    'remarks' => $studentData['remarks'] ?? null,
                    'marked_by' => auth()->id(),
                ]
            );
        }

        return $this->success($attendanceRecords, 'Attendance marked successfully', 201);
    }

    /**
     * Get attendance reports
     */
    public function reports(Request $request): JsonResponse
    {
        $termId = $request->get('term_id');
        $classId = $request->get('class_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Attendance::whereHas('term', function ($q) use ($request) {
            $q->whereHas('academicYear', function ($query) use ($request) {
                $query->where('school_id', $request->get('school_id'));
            });
        })->with(['student', 'class', 'term']);

        if ($termId) {
            $query->where('term_id', $termId);
        }

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $attendance = $query->get();

        // Calculate overall statistics
        $totalDays = $attendance->count();
        $present = $attendance->where('status', 'present')->count();
        $absent = $attendance->where('status', 'absent')->count();
        $late = $attendance->where('status', 'late')->count();
        $excused = $attendance->where('status', 'excused')->count();

        // Calculate by class
        $byClass = $attendance->groupBy('class_id')->map(function ($classAttendance) {
            return [
                'class_id' => $classAttendance->first()->class_id,
                'class_name' => $classAttendance->first()->class->name ?? 'N/A',
                'total' => $classAttendance->count(),
                'present' => $classAttendance->where('status', 'present')->count(),
                'absent' => $classAttendance->where('status', 'absent')->count(),
                'late' => $classAttendance->where('status', 'late')->count(),
                'excused' => $classAttendance->where('status', 'excused')->count(),
                'attendance_rate' => $classAttendance->count() > 0 
                    ? round((($classAttendance->where('status', 'present')->count() + $classAttendance->where('status', 'late')->count()) / $classAttendance->count()) * 100, 2)
                    : 0,
            ];
        })->values();

        // Calculate by date
        $byDate = $attendance->groupBy('date')->map(function ($dateAttendance, $date) {
            return [
                'date' => $date,
                'total' => $dateAttendance->count(),
                'present' => $dateAttendance->where('status', 'present')->count(),
                'absent' => $dateAttendance->where('status', 'absent')->count(),
                'late' => $dateAttendance->where('status', 'late')->count(),
                'excused' => $dateAttendance->where('status', 'excused')->count(),
            ];
        })->values()->sortBy('date')->values();

        return $this->success([
            'overall' => [
                'total_days' => $totalDays,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'excused' => $excused,
                'attendance_rate' => $totalDays > 0 ? round((($present + $late) / $totalDays) * 100, 2) : 0,
            ],
            'by_class' => $byClass,
            'by_date' => $byDate,
        ], 'Attendance reports retrieved successfully');
    }

    /**
     * Get classes and active term for attendance marking
     */
    public function getMarkingData(Request $request): JsonResponse
    {
        // Get active term
        $activeTerm = Term::whereHas('academicYear', function ($q) use ($request) {
            $q->where('school_id', $request->get('school_id'))
                ->where('is_active', true);
        })
            ->where('status', 'active')
            ->with('academicYear')
            ->first();

        // Get classes (for teachers, only their assigned classes)
        $classesQuery = \App\Models\ClassModel::where('school_id', $request->get('school_id'))
            ->where('is_active', true)
            ->with(['academicYear', 'classTeacher.user']);

        // If user is a teacher, filter to their classes only
        if (auth()->user()->isTeacher()) {
            $teacher = auth()->user()->teacher;
            $classesQuery->where(function ($q) use ($teacher) {
                $q->where('class_teacher_id', $teacher->id)
                    ->orWhereHas('subjects', function ($query) use ($teacher) {
                        $query->where('teacher_id', $teacher->id);
                    });
            });
        }

        $classes = $classesQuery->get();

        return $this->success([
            'active_term' => $activeTerm,
            'classes' => $classes,
        ], 'Marking data retrieved successfully');
    }

    /**
     * Get student attendance
     */
    public function studentAttendance(Request $request, $studentId): JsonResponse
    {
        $termId = $request->get('term_id');
        
        $query = Attendance::where('student_id', $studentId)
            ->with(['term', 'class']);

        if ($termId) {
            $query->where('term_id', $termId);
        }

        $attendance = $query->orderBy('date', 'desc')->get();

        // Calculate statistics
        $stats = [
            'total_days' => $attendance->count(),
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'excused' => $attendance->where('status', 'excused')->count(),
        ];

        return $this->success([
            'records' => $attendance,
            'statistics' => $stats,
        ], 'Student attendance retrieved successfully');
    }

    /**
     * Get class attendance
     */
    public function classAttendance(Request $request, $classId): JsonResponse
    {
        $termId = $request->get('term_id');
        $date = $request->get('date');

        $query = Attendance::where('class_id', $classId)
            ->with(['student', 'term']);

        if ($termId) {
            $query->where('term_id', $termId);
        }

        if ($date) {
            $query->where('date', $date);
        }

        $attendance = $query->orderBy('date', 'desc')->get();

        return $this->success($attendance, 'Class attendance retrieved successfully');
    }

    /**
     * Display the specified attendance record
     */
    public function show(Request $request, Attendance $attendance): JsonResponse
    {
        // Ensure attendance belongs to user's school
        if ($attendance->term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Attendance record not found', 404);
        }

        $attendance->load(['student', 'class', 'term', 'markedBy']);

        return $this->success($attendance, 'Attendance record retrieved successfully');
    }

    /**
     * Update the specified attendance record
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        // Ensure attendance belongs to user's school
        if ($attendance->term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Attendance record not found', 404);
        }

        // Check if user can edit this attendance
        // Only the user who marked it or school admins can edit
        $user = auth()->user();
        $canEdit = false;

        if ($user->isSuperAdmin() || $user->isSchoolAdmin()) {
            $canEdit = true;
        } elseif ($user->isTeacher() && $attendance->marked_by === $user->id) {
            $canEdit = true;
        }

        if (!$canEdit) {
            return $this->error('You do not have permission to edit this attendance record', 403);
        }

        $data = $request->validated();

        // Update attendance
        $attendance->update($data);

        $attendance->load(['student', 'class', 'term', 'markedBy']);

        return $this->success($attendance, 'Attendance record updated successfully');
    }

    /**
     * Generate PDF attendance sheet
     */
    public function generatePdf(Request $request)
    {
        // If token is provided as query parameter (for preview), authenticate with it
        if ($request->has('token')) {
            try {
                $token = $request->get('token');
                // Authenticate using the token with the API guard
                $user = JWTAuth::setToken($token)->authenticate();
                if (!$user) {
                    return $this->error('Invalid token', 401);
                }
                // Set the authenticated user for the API guard
                auth('api')->setUser($user);
                // Set school_id in request for school scoping (like the middleware does)
                if ($user->school_id) {
                    $request->merge(['school_id' => $user->school_id]);
                }
            } catch (\Exception $e) {
                return $this->error('Invalid token', 401);
            }
        }

        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $class = ClassModel::with(['academicYear', 'classTeacher.user', 'school'])->findOrFail($request->class_id);
        $term = Term::with('academicYear')->findOrFail($request->term_id);

        // Ensure class and term belong to user's school
        if ($class->school_id !== $request->get('school_id') || $term->academicYear->school_id !== $request->get('school_id')) {
            return $this->error('Class or term not found', 404);
        }

        // Get students for the class
        $students = $class->students()->orderBy('first_name')->orderBy('last_name')->get();

        // Get attendance records
        $query = Attendance::where('class_id', $class->id)
            ->where('term_id', $term->id)
            ->with(['student']);

        if ($request->has('date')) {
            $query->where('date', $request->date);
        } elseif ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } else {
            // Default to all attendance in the term
            $query->whereBetween('date', [$term->start_date, $term->end_date]);
        }

        $attendanceRecords = $query->get()->groupBy('date');

        // Get school information
        $school = School::find($request->get('school_id'));

        // Prepare data for PDF
        $data = [
            'school' => $school,
            'class' => $class,
            'term' => $term,
            'students' => $students,
            'attendanceRecords' => $attendanceRecords,
            'date' => $request->date ?? ($request->start_date && $request->end_date ? $request->start_date . ' to ' . $request->end_date : null),
            'generated_at' => now(),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('attendance.sheet', $data);
        $pdf->setPaper('A4', 'landscape');

        $action = $request->get('action', 'download'); // 'preview' or 'download'
        $filename = 'attendance_sheet_' . $class->name . '_' . ($request->date ?? 'range') . '.pdf';

        if ($action === 'preview') {
            // For preview, use stream with inline content disposition
            // This tells the browser to display the PDF inline, not download it
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                ->header('Pragma', 'public')
                ->header('Accept-Ranges', 'bytes');
        }

        // For download, use attachment content disposition
        return $pdf->download($filename);
    }
}

