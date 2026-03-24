<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('root');
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
});

Route::middleware(['auth', 'teacher'])->group(function () {
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
});

Route::middleware(['auth', 'student'])->group(function () {
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
});

Route::middleware(['auth', 'family'])->group(function () {
    Route::view('/family/dashboard', 'family.dashboard')->name('family.dashboard');
});
