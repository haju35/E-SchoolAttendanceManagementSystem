<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\FamilyController;
use App\Http\Controllers\Admin\ClassRoomController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\TermController;
use App\Http\Controllers\Admin\TeacherAssignmentController;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\SystemConfigController;

// Public Routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

Auth::routes();

// ========== ADMIN ROUTES ==========
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Students Management
    Route::resource('students', StudentController::class);
    Route::get('students/import/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
    Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('students/import/form', [StudentController::class, 'importForm'])->name('students.import');
    
    // Teachers Management
    Route::resource('teachers', TeacherController::class);
    
    // Families Management
    Route::resource('families', FamilyController::class);
    
    // Classes Management
    Route::resource('classes', ClassRoomController::class);
    Route::get('sections/by-class/{classId}', [SectionController::class, 'getByClass'])->name('sections.by-class');
    
    // Sections Management
    Route::resource('sections', SectionController::class);
    
    // Subjects Management
    Route::resource('subjects', SubjectController::class);
    
    // Academic Years Management
    Route::resource('academic-years', AcademicYearController::class);
    
    // Terms Management
    Route::resource('terms', TermController::class);
    
    // Teacher Assignments
    Route::resource('assignments', TeacherAssignmentController::class);
    Route::get('teachers/{teacherId}/assignments', [TeacherAssignmentController::class, 'byTeacher'])->name('assignments.by-teacher');
    Route::get('classes/{classId}/assignments', [TeacherAssignmentController::class, 'byClass'])->name('assignments.by-class');
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('attendance', [AttendanceReportController::class, 'index'])->name('attendance');
        Route::get('attendance/daily', [AttendanceReportController::class, 'daily'])->name('attendance.daily');
        Route::get('attendance/monthly', [AttendanceReportController::class, 'monthly'])->name('attendance.monthly');
        Route::get('attendance/student/{id}', [AttendanceReportController::class, 'student'])->name('attendance.student');
        Route::get('attendance/class/{id}', [AttendanceReportController::class, 'class'])->name('attendance.class');
        Route::get('attendance/export', [AttendanceReportController::class, 'export'])->name('attendance.export');
    });
    
    // System Configuration
    Route::get('config', [SystemConfigController::class, 'index'])->name('config');
    Route::put('config', [SystemConfigController::class, 'update'])->name('config.update');
});

// ========== TEACHER ROUTES ==========
Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::get('/attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [TeacherAttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/{id}', [TeacherAttendanceController::class, 'show'])->name('attendance.show');
    Route::put('/attendance/{id}', [TeacherAttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/attendance/{id}', [TeacherAttendanceController::class, 'destroy'])->name('attendance.destroy');
    Route::get('/classes', [TeacherClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/{id}/students', [TeacherClassController::class, 'students'])->name('classes.students');
    Route::get('/students/{id}', [TeacherStudentController::class, 'show'])->name('students.show');
    Route::get('/reports/attendance', [TeacherReportController::class, 'attendance'])->name('reports.attendance');
    Route::get('/profile', [TeacherProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [TeacherProfileController::class, 'update'])->name('profile.update');
});

// ========== STUDENT ROUTES ==========
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/summary', [StudentAttendanceController::class, 'summary'])->name('attendance.summary');
    Route::get('/profile', [StudentProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [StudentProfileController::class, 'update'])->name('profile.update');
});

// ========== FAMILY ROUTES ==========
Route::middleware(['auth', 'family'])->prefix('family')->name('family.')->group(function () {
    Route::get('/dashboard', [FamilyDashboardController::class, 'index'])->name('dashboard');
    Route::get('/children', [ChildController::class, 'index'])->name('children.index');
    Route::get('/children/{id}', [ChildController::class, 'show'])->name('children.show');
    Route::get('/children/{id}/attendance', [ChildController::class, 'attendance'])->name('children.attendance');
    Route::get('/children/{id}/summary', [ChildController::class, 'summary'])->name('children.summary');
    Route::get('/attendance', [FamilyAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/notifications', [FamilyNotificationController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/{id}/read', [FamilyNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/profile', [FamilyProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [FamilyProfileController::class, 'update'])->name('profile.update');
});

// Profile Route
Route::middleware('auth')->get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile');
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
