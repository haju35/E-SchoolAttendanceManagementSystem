<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\FamilyController;
use App\Http\Controllers\Admin\ClassRoomController;
use App\Http\Controllers\Admin\TeacherAssignmentController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\TermController;
use App\Http\Controllers\Admin\BulkImportController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Teacher\ClassController as TeacherClassController;
use App\Http\Controllers\Teacher\StudentController as TeacherStudentController;
use App\Http\Controllers\Teacher\ReportController as TeacherReportController;
use App\Http\Controllers\Teacher\ProfileController as TeacherProfileController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Family\DashboardController as FamilyDashboardController;
use App\Http\Controllers\Family\ChildController;
use App\Http\Controllers\Family\AttendanceController as FamilyAttendanceController;
use App\Http\Controllers\Family\NotificationController as FamilyNotificationController;
use App\Http\Controllers\Family\ProfileController as FamilyProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== PUBLIC ROUTES ==========
Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'apiLogin']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'apiSendResetLink']);
    Route::post('/reset-password', [ResetPasswordController::class, 'apiReset']);
});

// ========== AUTHENTICATED USER ROUTES ==========
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::get('/user', [LoginController::class, 'apiUser']);
    Route::post('/logout', [LoginController::class, 'apiLogout']);
    Route::post('/refresh', [LoginController::class, 'apiRefresh']);
    Route::get('/profile', [RegisterController::class, 'apiProfile']);
    Route::put('/profile', [RegisterController::class, 'apiUpdateProfile']);
    Route::post('/change-password', [RegisterController::class, 'apiChangePassword']);
    Route::get('/dashboard', [LoginController::class, 'apiDashboard']);
});

// ========== ROLE-SPECIFIC DASHBOARDS ==========
Route::middleware('auth:api')->group(function () {
    Route::get('/auth/admin/dashboard', [LoginController::class, 'apiAdminDashboard'])->middleware('admin');
    Route::get('/auth/teacher/dashboard', [LoginController::class, 'apiTeacherDashboard'])->middleware('teacher');
    Route::get('/auth/student/dashboard', [LoginController::class, 'apiStudentDashboard'])->middleware('student');
    Route::get('/auth/family/dashboard', [LoginController::class, 'apiFamilyDashboard'])->middleware('family');
});

// ========== ADMIN PANEL ==========
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Student Management
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/students/{id}', [StudentController::class, 'show']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);
    Route::post('/students/import', [StudentController::class, 'import']);
    Route::get('/students/export/template', [BulkImportController::class, 'studentTemplate']);

    // Teacher Management
    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::post('/teachers', [TeacherController::class, 'store']);
    Route::get('/teachers/{id}', [TeacherController::class, 'show']);
    Route::put('/teachers/{id}', [TeacherController::class, 'update']);
    Route::delete('/teachers/{id}', [TeacherController::class, 'destroy']);

    // Family Management
    Route::get('/families', [FamilyController::class, 'index']);
    Route::post('/families', [FamilyController::class, 'store']);
    Route::get('/families/{id}', [FamilyController::class, 'show']);
    Route::put('/families/{id}', [FamilyController::class, 'update']);
    Route::delete('/families/{id}', [FamilyController::class, 'destroy']);

    // Class Management
    Route::get('/classes', [ClassRoomController::class, 'index']);
    Route::post('/classes', [ClassRoomController::class, 'store']);
    Route::get('/classes/{id}', [ClassRoomController::class, 'show']);
    Route::put('/classes/{id}', [ClassRoomController::class, 'update']);
    Route::delete('/classes/{id}', [ClassRoomController::class, 'destroy']);

    // Section Management
    Route::get('/sections', [SectionController::class, 'index']);
    Route::post('/sections', [SectionController::class, 'store']);
    Route::post('/sections/bulk', [SectionController::class, 'bulkStore']);
    Route::get('/sections/{id}', [SectionController::class, 'show']);
    Route::put('/sections/{id}', [SectionController::class, 'update']);
    Route::delete('/sections/{id}', [SectionController::class, 'destroy']);


    // Subject Management
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::post('/subjects', [SubjectController::class, 'store']);
    Route::get('/subjects/{id}', [SubjectController::class, 'show']);
    Route::put('/subjects/{id}', [SubjectController::class, 'update']);
    Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);

    // Academic Year Management
    Route::get('/academic-years', [AcademicYearController::class, 'index']);
    Route::post('/academic-years', [AcademicYearController::class, 'store']);
    Route::get('/academic-years/{id}', [AcademicYearController::class, 'show']);
    Route::put('/academic-years/{id}', [AcademicYearController::class, 'update']);
    Route::delete('/academic-years/{id}', [AcademicYearController::class, 'destroy']);

    // Term Management
    Route::get('/terms', [TermController::class, 'index']);
    Route::post('/terms', [TermController::class, 'store']);
    Route::get('/terms/{id}', [TermController::class, 'show']);
    Route::put('/terms/{id}', [TermController::class, 'update']);
    Route::delete('/terms/{id}', [TermController::class, 'destroy']);

    // Reports
    Route::get('/reports/attendance/daily', [AttendanceReportController::class, 'daily']);
    Route::get('/reports/attendance/monthly', [AttendanceReportController::class, 'monthly']);
    Route::get('/reports/attendance/student/{id}', [AttendanceReportController::class, 'byStudent']);
    Route::get('/reports/attendance/class/{id}', [AttendanceReportController::class, 'byClass']);

    //teacher assignment route
    Route::get('/teacher-assignments', [TeacherAssignmentController::class, 'index']);
    Route::post('/teacher-assignments', [TeacherAssignmentController::class, 'store']);
    Route::get('/teacher-assignments/{id}', [TeacherAssignmentController::class, 'show']);
    Route::put('/teacher-assignments/{id}', [TeacherAssignmentController::class, 'update']);
    Route::delete('/teacher-assignments/{id}', [TeacherAssignmentController::class, 'destroy']);
    Route::get('/classes/{classId}/teacher-assignments', [TeacherAssignmentController::class, 'getByClassAndSection']);
    Route::get('/teachers/{teacherId}/assignments', [TeacherAssignmentController::class, 'getByTeacher']);

    // System Configuration
    Route::get('/config', [SystemConfigController::class, 'index']);
    Route::put('/config', [SystemConfigController::class, 'update']);

    // Subject assignments routes
    Route::get('/classes/{classId}/subjects', [ClassSubjectController::class, 'getSubjects']);
    Route::post('/classes/{classId}/subjects', [ClassSubjectController::class, 'assignSubject']);
    Route::put('/classes/{classId}/subjects/{subjectId}', [ClassSubjectController::class, 'updateSubjectAssignment']);
    Route::delete('/classes/{classId}/subjects/{subjectId}', [ClassSubjectController::class, 'removeSubject']);
});

// ========== TEACHER PANEL ==========
Route::middleware(['auth:api', 'teacher'])->prefix('teacher')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index']);
    Route::get('/attendance', [TeacherAttendanceController::class, 'index']);
    Route::post('/attendance', [TeacherAttendanceController::class, 'store']);
    Route::get('/attendance/{id}', [TeacherAttendanceController::class, 'show']);
    Route::put('/attendance/{id}', [TeacherAttendanceController::class, 'update']);
    Route::delete('/attendance/{id}', [TeacherAttendanceController::class, 'destroy']);
    Route::get('/classes', [TeacherClassController::class, 'index']);
    Route::get('/classes/{id}/students', [TeacherClassController::class, 'students']);
    Route::get('/students/{id}', [TeacherStudentController::class, 'show']);
    Route::get('/reports/attendance', [TeacherReportController::class, 'attendance']);
    Route::get('/profile', [TeacherProfileController::class, 'show']);
    Route::put('/profile', [TeacherProfileController::class, 'update']);
});

// ========== STUDENT PANEL ==========
Route::middleware(['auth:api', 'student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index']);
    Route::get('/attendance', [StudentAttendanceController::class, 'index']);
    Route::get('/attendance/summary', [StudentAttendanceController::class, 'summary']);
    Route::get('/profile', [StudentProfileController::class, 'show']);
    Route::put('/profile', [StudentProfileController::class, 'update']);
});

// ========== FAMILY PANEL ==========
Route::middleware(['auth:api', 'family'])->prefix('family')->group(function () {
    Route::get('/dashboard', [FamilyDashboardController::class, 'index']);
    Route::get('/children', [ChildController::class, 'index']);
    Route::get('/children/{id}', [ChildController::class, 'show']);
    Route::get('/children/{id}/attendance', [ChildController::class, 'attendance']);
    Route::get('/children/{id}/summary', [ChildController::class, 'summary']);
    Route::get('/attendance', [FamilyAttendanceController::class, 'index']);
    Route::get('/notifications', [FamilyNotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [FamilyNotificationController::class, 'markAsRead']);
    Route::get('/profile', [FamilyProfileController::class, 'show']);
    Route::put('/profile', [FamilyProfileController::class, 'update']);
});
