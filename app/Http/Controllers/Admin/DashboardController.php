<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Family;
use App\Models\Attendance;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $totalStudents = Student::count();
            $totalTeachers = Teacher::count();
            $totalFamilies = Family::count();
            
            $today = now()->format('Y-m-d');
            $presentToday = Attendance::whereDate('date', $today)->where('status', 'present')->count();
            $absentToday = Attendance::whereDate('date', $today)->where('status', 'absent')->count();
            $lateToday = Attendance::whereDate('date', $today)->where('status', 'late')->count();
            
            $totalMarkedToday = $presentToday + $absentToday + $lateToday;

            $attendancePercentage = $totalMarkedToday > 0
                ? round(($presentToday / $totalMarkedToday) * 100)
                : 0;

            $recentStudents = Student::with(['user', 'currentClass'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($student) => [
                    'id' => $student->id,
                    'user' => ['name' => $student->user?->name ?? 'N/A'],
                    'admission_number' => $student->admission_number,
                    'currentClass' => $student->currentClass?->name,
                    'created_at' => $student->created_at
                ]);

            $recentTeachers = Teacher::with('user')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($teacher) => [
                    'id' => $teacher->id,
                    'user' => ['name' => $teacher->user?->name ?? 'N/A'],
                    'qualification' => $teacher->qualification,
                    'created_at' => $teacher->created_at
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_students' => $totalStudents,
                    'total_teachers' => $totalTeachers,
                    'total_families' => $totalFamilies,
                    'attendance_percentage' => $attendancePercentage,
                    'present_today' => $presentToday,
                    'absent_today' => $absentToday,
                    'late_today' => $lateToday,
                    'recent_students' => $recentStudents,
                    'recent_teachers' => $recentTeachers
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data'
            ], 500);
        }
    }
}