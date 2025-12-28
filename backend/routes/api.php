<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\ReportCardController;
use App\Http\Controllers\Api\TermController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ExcelImportController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\GuardianController;
use App\Http\Controllers\Api\GradingScaleController;
use App\Http\Controllers\Api\FeeController;
use App\Http\Controllers\Api\SubscriptionPriceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected routes (authentication required)
Route::middleware(['auth:api', 'school.scope'])->group(function () {
    // Dashboard Statistics
    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics']);
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Students
    Route::apiResource('students', StudentController::class);
    Route::get('/students/{student}/results', [StudentController::class, 'results']);
    Route::get('/students/{student}/attendance', [StudentController::class, 'attendance']);
    Route::get('/students/{student}/enrollments', [StudentController::class, 'enrollments']);
    Route::post('/students/{student}/guardians', [StudentController::class, 'linkGuardian'])->middleware('role:super_admin,school_admin');
    Route::delete('/students/{student}/guardians/{guardian}', [StudentController::class, 'unlinkGuardian'])->middleware('role:super_admin,school_admin');

    // Users Management (School Admin only)
    Route::apiResource('users', UserController::class)->middleware('role:school_admin');
    
    // Teachers
    Route::apiResource('teachers', TeacherController::class)->middleware('role:super_admin,school_admin');
    Route::get('/teachers/{teacher}/classes', [TeacherController::class, 'classes']);
    Route::post('/teachers/{teacher}/assign-class', [TeacherController::class, 'assignClass'])->middleware('role:super_admin,school_admin');
    Route::post('/teachers/{teacher}/assign-subject', [TeacherController::class, 'assignSubject'])->middleware('role:super_admin,school_admin');
    Route::delete('/teachers/{teacher}/classes/{class}', [TeacherController::class, 'removeClass'])->middleware('role:super_admin,school_admin');
    Route::delete('/teachers/{teacher}/subjects/{classSubject}', [TeacherController::class, 'removeSubject'])->middleware('role:super_admin,school_admin');

    // Classes
    Route::apiResource('classes', ClassController::class);
    Route::get('/classes/{class}/students', [ClassController::class, 'students']);
    Route::get('/classes/{class}/subjects', [ClassController::class, 'subjects']);
    Route::post('/classes/{class}/students', [ClassController::class, 'addStudent'])->middleware('role:super_admin,school_admin');
    Route::post('/classes/{class}/subjects', [ClassController::class, 'assignSubject'])->middleware('role:super_admin,school_admin');
    Route::delete('/classes/{class}/subjects/{classSubject}', [ClassController::class, 'removeSubject'])->middleware('role:super_admin,school_admin');

    // Subjects
    Route::apiResource('subjects', SubjectController::class)->middleware('role:super_admin,school_admin');

    // Attendance
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('/marking-data', [AttendanceController::class, 'getMarkingData'])->middleware('role:super_admin,school_admin,teacher');
        Route::post('/mark', [AttendanceController::class, 'mark'])->middleware('role:super_admin,school_admin,teacher');
        Route::get('/reports', [AttendanceController::class, 'reports']);
        Route::get('/pdf', [AttendanceController::class, 'generatePdf']);
        Route::get('/student/{studentId}', [AttendanceController::class, 'studentAttendance'])->middleware('parent.subscription');
        Route::get('/class/{classId}', [AttendanceController::class, 'classAttendance']);
        Route::get('/{attendance}', [AttendanceController::class, 'show']);
        Route::put('/{attendance}', [AttendanceController::class, 'update'])->middleware('role:super_admin,school_admin,teacher');
    });

    // Exams
    Route::apiResource('exams', ExamController::class);
    Route::get('/exams/{exam}/results', [ExamController::class, 'results']);

    // Assessments
    Route::apiResource('assessments', AssessmentController::class)->middleware('term.status');
    Route::get('/assessments/{assessment}/results', [AssessmentController::class, 'results']);

    // Results
    Route::prefix('results')->group(function () {
        Route::get('/', [ResultController::class, 'index']);
        Route::post('/enter', [ResultController::class, 'enter'])->middleware('role:super_admin,school_admin,teacher');
        Route::get('/student/{studentId}', [ResultController::class, 'studentResults'])->middleware('parent.subscription');
        Route::get('/student/{studentId}/term/{termId}', [ResultController::class, 'studentTermResults'])->middleware('parent.subscription');
        Route::get('/assessment/{assessmentId}', [ResultController::class, 'assessmentResults']);
    });

    // Report Cards
    Route::prefix('report-cards')->group(function () {
        Route::get('/', [ReportCardController::class, 'index']);
        Route::post('/generate', [ReportCardController::class, 'generate'])->middleware('role:super_admin,school_admin,teacher');
        Route::get('/student/{studentId}/term/{termId}', [ReportCardController::class, 'show'])->middleware('parent.subscription');
        Route::get('/pdf', [ReportCardController::class, 'generatePdf']);
    });

    // Terms - Viewable by all, but create/update/delete restricted to admins
    Route::get('/terms', [TermController::class, 'index']);
    Route::get('/terms/{term}', [TermController::class, 'show']);
    Route::post('/terms', [TermController::class, 'store'])->middleware('role:super_admin,school_admin');
    Route::put('/terms/{term}', [TermController::class, 'update'])->middleware('role:super_admin,school_admin');
    Route::delete('/terms/{term}', [TermController::class, 'destroy'])->middleware('role:super_admin,school_admin');
    Route::post('/terms/{term}/activate', [TermController::class, 'activate'])->middleware('role:super_admin,school_admin');
    Route::post('/terms/{term}/start-closing', [TermController::class, 'startClosing'])->middleware('role:super_admin,school_admin');
    Route::post('/terms/{term}/close', [TermController::class, 'close'])->middleware('role:super_admin,school_admin');
    Route::post('/terms/{term}/archive', [TermController::class, 'archive'])->middleware('role:super_admin,school_admin');

    // Academic Years
    Route::apiResource('academic-years', AcademicYearController::class)->middleware('role:super_admin,school_admin');
    Route::post('/academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate'])->middleware('role:super_admin,school_admin');

    // Parent Portal
    Route::prefix('parent')->middleware('role:parent')->group(function () {
        Route::get('/children', [ParentController::class, 'children']);
        Route::post('/link-child', [ParentController::class, 'linkChild']);
        Route::delete('/unlink-child/{studentId}', [ParentController::class, 'unlinkChild']);
        Route::get('/subscriptions', [ParentController::class, 'subscriptions']);
        Route::get('/payments', [ParentController::class, 'payments']);
        Route::post('/payments', [PaymentController::class, 'store']);
    });

    // Guardians Management (Admin only)
    Route::prefix('guardians')->middleware('role:super_admin,school_admin')->group(function () {
        Route::get('/', [GuardianController::class, 'index']);
        Route::post('/', [GuardianController::class, 'store']);
        Route::get('/{guardian}', [GuardianController::class, 'show']);
    });

    // Subscriptions (Admin)
    Route::apiResource('subscriptions', SubscriptionController::class)->middleware('role:super_admin,school_admin');
    Route::get('/subscriptions/student/{studentId}', [SubscriptionController::class, 'studentSubscriptions']);
    Route::get('/subscriptions/parent/{parentId}', [SubscriptionController::class, 'parentSubscriptions']);

    // Payments (Admin - filter by payment_type)
    Route::apiResource('payments', PaymentController::class)->middleware('role:super_admin,school_admin,accounts_manager');
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->middleware('role:super_admin,school_admin,accounts_manager');
    Route::post('/payments/initiate-fee', [PaymentController::class, 'initiateFeePayment'])->middleware('role:school_admin,accounts_manager');
    Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('api.payments.webhook'); // Public webhook endpoint
    
    // Fees Management (School Admin & Accounts Manager)
    Route::apiResource('fees', FeeController::class)->middleware('role:super_admin,school_admin,accounts_manager');
    Route::get('/fees/term/{termId}', [FeeController::class, 'getTermFee']); // Public endpoint for parents to check fee
    
    // Subscription Prices Management (Super Admin only)
    Route::apiResource('subscription-prices', SubscriptionPriceController::class)->middleware('role:super_admin');
    Route::get('/subscription-prices/student/{studentId}', [SubscriptionPriceController::class, 'getStudentPrice']); // Public endpoint for parents
    
    // Parent Payment Routes
    Route::prefix('parent')->middleware('role:parent')->group(function () {
        Route::get('/payments/{payment}/status', [PaymentController::class, 'checkStatus']);
        Route::post('/payments/{payment}/retry', [PaymentController::class, 'retry']);
        Route::post('/payments/verify', [PaymentController::class, 'verifyPayment']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

    // Settings (Admin only)
    Route::prefix('settings')->middleware('role:super_admin,school_admin')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/', [SettingsController::class, 'update']);
    });

    // Grading Scales (Admin only)
    Route::prefix('grading-scales')->middleware('role:super_admin,school_admin')->group(function () {
        Route::get('/', [GradingScaleController::class, 'index']);
        Route::post('/', [GradingScaleController::class, 'store']);
        Route::get('/{gradingScale}', [GradingScaleController::class, 'show']);
        Route::put('/{gradingScale}', [GradingScaleController::class, 'update']);
        Route::delete('/{gradingScale}', [GradingScaleController::class, 'destroy']);
        Route::post('/{gradingScale}/set-default', [GradingScaleController::class, 'setDefault']);
    });

    // Excel Import/Export
    Route::prefix('excel')->middleware('role:super_admin,school_admin')->group(function () {
        // Download templates
        Route::get('/templates/students', [ExcelImportController::class, 'downloadStudentTemplate']);
        Route::get('/templates/teachers', [ExcelImportController::class, 'downloadTeacherTemplate']);
        Route::get('/templates/classes', [ExcelImportController::class, 'downloadClassTemplate']);
        Route::get('/templates/subjects', [ExcelImportController::class, 'downloadSubjectTemplate']);
        Route::get('/templates/class-students', [ExcelImportController::class, 'downloadClassStudentTemplate']);

        // Import data
        Route::post('/import/students', [ExcelImportController::class, 'importStudents']);
        Route::post('/import/teachers', [ExcelImportController::class, 'importTeachers']);
        Route::post('/import/classes', [ExcelImportController::class, 'importClasses']);
        Route::post('/import/subjects', [ExcelImportController::class, 'importSubjects']);
        Route::post('/import/class-students', [ExcelImportController::class, 'importClassStudents']);

        // Export data
        Route::get('/export/class-students', [ExcelImportController::class, 'exportClassStudents']);
    });

    // Schools Management (Super Admin only)
    Route::prefix('schools')->middleware('role:super_admin')->group(function () {
        Route::get('/', [SchoolController::class, 'index']);
        Route::post('/', [SchoolController::class, 'store']);
        Route::get('/{school}', [SchoolController::class, 'show']);
        Route::put('/{school}', [SchoolController::class, 'update']);
        Route::delete('/{school}', [SchoolController::class, 'destroy']);
        Route::get('/{school}/statistics', [SchoolController::class, 'statistics']);
        Route::post('/{school}/toggle-status', [SchoolController::class, 'toggleStatus']);
    });

    // Sign in as school admin (Super Admin only)
    Route::prefix('auth')->group(function () {
        Route::post('/sign-in-as-school/{school}', [AuthController::class, 'signInAsSchool'])->middleware('role:super_admin');
    });
});

