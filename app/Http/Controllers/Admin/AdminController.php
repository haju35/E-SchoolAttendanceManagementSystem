<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Family;
use App\Models\ClassRoom;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        try {
            // Get counts - REMOVED status filtering
            $totalStudents = Student::count();
            $totalTeachers = Teacher::count();
            $totalFamilies = Family::count();
            
            // Get today's attendance
            $today = now()->format('Y-m-d');
            $presentToday = Attendance::whereDate('date', $today)->where('status', 'present')->count();
            $absentToday = Attendance::whereDate('date', $today)->where('status', 'absent')->count();
            $lateToday = Attendance::whereDate('date', $today)->where('status', 'late')->count();
            
            // Calculate attendance percentage
            $attendancePercentage = $totalStudents > 0 
                ? round(($presentToday / $totalStudents) * 100) 
                : 0;
            
            // Get recent students with their class info
            $recentStudents = Student::with(['user', 'currentClass'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function($student) {
                    return [
                        'id' => $student->id,
                        'user' => [
                            'name' => $student->user ? $student->user->name : 'N/A'
                        ],
                        'admission_number' => $student->admission_number,
                        'currentClass' => $student->currentClass ? [
                            'name' => $student->currentClass->name
                        ] : null,
                        'created_at' => $student->created_at
                    ];
                });
            
            // Get recent teachers
            $recentTeachers = Teacher::with('user')
                ->latest()
                ->take(5)
                ->get()
                ->map(function($teacher) {
                    return [
                        'id' => $teacher->id,
                        'user' => [
                            'name' => $teacher->user ? $teacher->user->name : 'N/A'
                        ],
                        'employee_id' => $teacher->employee_id,
                        'qualification' => $teacher->qualification,
                        'created_at' => $teacher->created_at
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_students' => $totalStudents,
                    'active_students' => $totalStudents, // Just use total since no status column
                    'total_teachers' => $totalTeachers,
                    'active_teachers' => $totalTeachers, // Just use total since no status column
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
                'message' => 'Failed to load dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }
}