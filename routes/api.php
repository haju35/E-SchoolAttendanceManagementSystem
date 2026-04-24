<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\FamilyController;
use App\Http\Controllers\Admin\ClassRoomController;
use App\Http\Controllers\Admin\ClassTeacherController;
use App\Http\Controllers\Admin\ClassSubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TeacherAssignmentController;
use App\Http\Controllers\Admin\ClassAttendanceController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\ProfileController;
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
    Route::post('/setup/create-admin', [AdminController::class, 'createAdmin']);
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

// ========== ADMIN PANEL ==========
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view dashboard');
    
    // User Management
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:view users');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:create users');
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('permission:edit users');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])
        ->middleware('permission:edit users');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])
        ->middleware('permission:delete users');
    Route::get('/roles', [UserController::class, 'getRoles']);
    
    // Role & Permission Management
    Route::get('/roles-permissions', [AdminController::class, 'rolesPermissions'])
        ->middleware('permission:view roles');
    Route::post('/roles', [AdminController::class, 'createRole'])
        ->middleware('permission:create roles');
    Route::put('/roles/{id}', [AdminController::class, 'updateRole'])
        ->middleware('permission:edit roles');
    Route::delete('/roles/{id}', [AdminController::class, 'deleteRole'])
        ->middleware('permission:delete roles');
    Route::post('/permissions', [AdminController::class, 'createPermission'])
        ->middleware('permission:create permissions');
    Route::post('/roles/{id}/permissions', [AdminController::class, 'assignPermissionsToRole'])
        ->middleware('permission:manage permissions');
    Route::post('/users/{id}/role', [AdminController::class, 'assignRoleToUser'])
        ->middleware('permission:manage permissions');
    Route::get('/users/{id}/permissions', [AdminController::class, 'getUserPermissions'])
        ->middleware('permission:view users');
    
    // Student Management
    Route::get('/students', [StudentController::class, 'index'])
        ->middleware('permission:view students');
    Route::post('/students', [StudentController::class, 'store'])
        ->middleware('permission:create students');
    Route::get('/students/{id}', [StudentController::class, 'show'])
        ->middleware('permission:view students');
    Route::put('/students/{id}', [StudentController::class, 'update'])
        ->middleware('permission:edit students');
    Route::delete('/students/{id}', [StudentController::class, 'destroy'])
        ->middleware('permission:delete students');
    Route::post('/students/{id}', [StudentController::class, 'destroy'])
        ->middleware('permission:delete students');
    Route::post('/students/import', [StudentController::class, 'import'])
        ->middleware('permission:import students');
    
    // Teacher Management
    Route::get('/teachers', [TeacherController::class, 'index'])
        ->middleware('permission:view teachers');
    Route::post('/teachers', [TeacherController::class, 'store'])
        ->middleware('permission:create teachers');
    Route::get('/teachers/{id}', [TeacherController::class, 'show'])
        ->middleware('permission:view teachers');
    Route::put('/teachers/{id}', [TeacherController::class, 'update'])
        ->middleware('permission:edit teachers');
    Route::delete('/teachers/{id}', [TeacherController::class, 'destroy'])
        ->middleware('permission:delete teachers');
    
    // Family Management
    Route::get('/families', [FamilyController::class, 'index'])
        ->middleware('permission:view families');
    Route::post('/families', [FamilyController::class, 'store'])
        ->middleware('permission:create families');
    Route::get('/families/{id}', [FamilyController::class, 'show'])
        ->middleware('permission:view families');
    Route::put('/families/{id}', [FamilyController::class, 'update'])
        ->middleware('permission:edit families');
    Route::delete('/families/{id}', [FamilyController::class, 'destroy'])
        ->middleware('permission:delete families');
    
    // Class Management
    Route::get('/classes', [ClassRoomController::class, 'index'])
        ->middleware('permission:view classes');
    Route::post('/classes', [ClassRoomController::class, 'store'])
        ->middleware('permission:create classes');
    Route::get('/classes/{id}', [ClassRoomController::class, 'show'])
        ->middleware('permission:view classes');
    Route::put('/classes/{id}', [ClassRoomController::class, 'update'])
        ->middleware('permission:edit classes');
    Route::delete('/classes/{id}', [ClassRoomController::class, 'destroy'])
        ->middleware('permission:delete classes');
    
    // Section Management
    Route::get('/sections', [SectionController::class, 'index'])
        ->middleware('permission:view sections');
    Route::post('/sections', [SectionController::class, 'store'])
        ->middleware('permission:create sections');
    Route::put('/sections/{id}', [SectionController::class, 'update'])
        ->middleware('permission:edit sections');
    Route::delete('/sections/{id}', [SectionController::class, 'destroy'])
        ->middleware('permission:delete sections');
    Route::get('/classes/{id}/sections', [SectionController::class, 'getByClass'])
        ->middleware('permission:view sections');
    
    // Subject Management
    Route::get('/subjects', [SubjectController::class, 'index'])
        ->middleware('permission:view subjects');
    Route::post('/subjects', [SubjectController::class, 'store'])
        ->middleware('permission:create subjects');
    Route::put('/subjects/{id}', [SubjectController::class, 'update'])
        ->middleware('permission:edit subjects');
    Route::delete('/subjects/{id}', [SubjectController::class, 'destroy'])
        ->middleware('permission:delete subjects');
    
    // Academic Year Management
    Route::get('/academic-years', [AcademicYearController::class, 'index'])
        ->middleware('permission:manage academic years');
    Route::post('/academic-years', [AcademicYearController::class, 'store'])
        ->middleware('permission:manage academic years');
    Route::put('/academic-years/{id}', [AcademicYearController::class, 'update'])
        ->middleware('permission:manage academic years');
    Route::delete('/academic-years/{id}', [AcademicYearController::class, 'destroy'])
        ->middleware('permission:manage academic years');

    Route::get('/profile', [ProfileController::class, 'getProfile']);
    Route::put('/profile', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    
    // Term Management
    Route::get('/terms', [TermController::class, 'index'])
        ->middleware('permission:manage terms');
    Route::post('/terms', [TermController::class, 'store'])
        ->middleware('permission:manage terms');
    Route::put('/terms/{id}', [TermController::class, 'update'])
        ->middleware('permission:manage terms');
    Route::delete('/terms/{id}', [TermController::class, 'destroy'])
        ->middleware('permission:manage terms');
    
    // Attendance Reports
    Route::get('/reports/attendance/daily', [AttendanceReportController::class, 'daily'])
        ->middleware('permission:view attendance');
    Route::get('/reports/attendance/monthly', [AttendanceReportController::class, 'monthly'])
        ->middleware('permission:view attendance');
    Route::get('/reports/attendance/student/{id}', [AttendanceReportController::class, 'byStudent'])
        ->middleware('permission:view attendance');
    Route::get('/reports/attendance/class/{id}', [AttendanceReportController::class, 'byClass'])
        ->middleware('permission:view attendance');
    
    // Teacher Assignment
    Route::get('/teacher-assignments', [TeacherAssignmentController::class, 'index'])
        ->middleware('permission:view teacher assignments');
    Route::post('/teacher-assignments', [TeacherAssignmentController::class, 'store'])
        ->middleware('permission:create teacher assignments');
    Route::put('/teacher-assignments/{id}', [TeacherAssignmentController::class, 'update'])
        ->middleware('permission:edit teacher assignments');
    Route::delete('/teacher-assignments/{id}', [TeacherAssignmentController::class, 'destroy'])
        ->middleware('permission:delete teacher assignments');


    // Class Teacher Assignment
    Route::get('/class-teachers', [ClassTeacherController::class, 'index']);
    Route::get('/class-teachers/list', [ClassTeacherController::class, 'list']);
    Route::post('/class-teachers', [ClassTeacherController::class, 'store']);      
    Route::get('/class-teachers/{id}', [ClassTeacherController::class, 'show']);
    Route::put('/class-teachers/{id}', [ClassTeacherController::class, 'update']);
    Route::delete('/class-teachers/{id}', [ClassTeacherController::class, 'destroy']);
    

    // System Configuration
    Route::get('/config', [SystemConfigController::class, 'index'])
        ->middleware('permission:manage settings');
    Route::put('/config', [SystemConfigController::class, 'update'])
        ->middleware('permission:manage settings');
});

// ========== TEACHER PANEL ==========
Route::middleware(['auth:api', 'role:teacher'])->prefix('teacher')->group(function () {

    Route::get('/profile', [TeacherProfileController::class, 'show']);
    Route::put('/profile', [TeacherProfileController::class, 'update']);
    Route::post('/profile/photo', [TeacherProfileController::class, 'uploadPhoto']);
    Route::post('/profile/change-password', [TeacherProfileController::class, 'changePassword']);
    
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])
        ->middleware('permission:view dashboard');

    Route::get('/students/{id}', [TeacherStudentController::class, 'show']);
    
    // Attendance Management
    Route::get('/attendance', [TeacherAttendanceController::class, 'index'])
        ->middleware('permission:view attendance');
    
    Route::post('/attendance', [TeacherAttendanceController::class, 'store'])
        ->middleware('permission:mark attendance'); 
    
    Route::put('/attendance/{id}', [TeacherAttendanceController::class, 'update'])
        ->middleware('permission:edit attendance');
    
    Route::delete('/attendance/{id}', [TeacherAttendanceController::class, 'destroy'])
        ->middleware('permission:delete attendance');
    
    // Class Attendance
    Route::get('/class-teacher/dashboard', [App\Http\Controllers\Teacher\ClassAttendanceController::class, 'classTeacherDashboard'])
        ->middleware('permission:view dashboard');
    
    Route::get('/class-teacher/classes', [App\Http\Controllers\Teacher\ClassAttendanceController::class, 'getClassTeacherClasses'])
        ->middleware('permission:view classes');
    
    Route::post('/attendance/class', [App\Http\Controllers\Teacher\ClassAttendanceController::class, 'markClassAttendance'])
        ->middleware('permission:mark attendance');
    
    Route::get('/class-attendance/students', [App\Http\Controllers\Teacher\ClassAttendanceController::class, 'getClassStudents'])
        ->middleware('permission:view students');
    
    Route::post('/class-attendance/mark', [App\Http\Controllers\Teacher\ClassAttendanceController::class, 'markClassAttendance'])
        ->middleware('permission:mark attendance');
    
    Route::get('/class-attendance', [App\Http\Controllers\Teacher\ClassAttendanceController::class, 'getClassAttendance'])
        ->middleware('permission:view attendance');
    
    Route::get('/class-attendance/student/{studentId}', [App\Http\Controllers\Teacher\ClassAttendanceController::class, 'getStudentClassAttendance'])
        ->middleware('permission:view attendance');
    
    // Classes & Students
    Route::get('/classes', [TeacherClassController::class, 'index'])
        ->middleware('permission:view classes');
    
    Route::get('/classes/{id}/students', [TeacherClassController::class, 'students'])
        ->middleware('permission:view students');
    
    Route::get('/students/{id}', [TeacherStudentController::class, 'show'])
        ->middleware('permission:view students');
    
    // Reports
    Route::get('/reports/attendance', [TeacherReportController::class, 'attendance'])
        ->middleware('permission:view reports');
    Route::get('/students/{id}/attendance', [TeacherStudentController::class, 'getStudentAttendance'])
        ->middleware('permission:view attendance');
    Route::get('/attendance/monthly-summary', [App\Http\Controllers\Teacher\ClassAttendanceController::class, 'monthlySummary']);
    
});

// ========== STUDENT PANEL ==========
Route::middleware(['auth:api', 'role:student'])->prefix('student')->group(function () {
    
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])
        ->middleware('permission:view dashboard');
    
    Route::get('/attendance', [StudentAttendanceController::class, 'index'])
        ->middleware('permission:view attendance');
    
    Route::get('/attendance/summary', [StudentAttendanceController::class, 'summary'])
        ->middleware('permission:view attendance');
    
    Route::get('/profile', [StudentProfileController::class, 'show']);
    Route::put('/profile', [StudentProfileController::class, 'update']);
    Route::put('/password', [StudentProfileController::class, 'updatePassword']);
    Route::post('/photo', [StudentProfileController::class, 'uploadPhoto']);
    Route::post('/profile/change-password', [StudentProfileController::class, 'updatePassword']);
});

// ========== FAMILY PANEL ==========
Route::middleware(['auth:api', 'role:family'])->prefix('family')->group(function () {
    
    Route::get('/dashboard', [FamilyDashboardController::class, 'index'])
        ->middleware('permission:view dashboard');
    
    Route::get('/children', [ChildController::class, 'index'])
        ->middleware('permission:view students');
    
    Route::get('/children/{id}', [ChildController::class, 'show'])
        ->middleware('permission:view students');
    
    Route::get('/children/{id}/attendance', [ChildController::class, 'attendance'])
        ->middleware('permission:view attendance');
    
    Route::get('/children/{id}/summary', [ChildController::class, 'summary'])
        ->middleware('permission:view attendance');
    
    Route::get('/attendance', [FamilyAttendanceController::class, 'index'])
        ->middleware('permission:view attendance');
    
    Route::get('/notifications', [FamilyNotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [FamilyNotificationController::class, 'markAsRead']);
    Route::get('/profile', [FamilyProfileController::class, 'getprofile']);
    Route::put('/profile', [FamilyProfileController::class, 'updateprofile']);
    Route::post('/profile/upload-photo', [FamilyProfileController::class, 'uploadPhoto']);
    Route::put('/change-password', [FamilyProfileController::class, 'changePassword']);
});