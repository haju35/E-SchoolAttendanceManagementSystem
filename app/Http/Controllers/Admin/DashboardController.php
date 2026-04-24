<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Family;
use App\Models\Attendance;
use App\Models\ClassRoom as Classes; // Adjust based on your actual model name
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Basic stats - Use simple counts first to test
            $totalStudents = Student::count();
            $totalTeachers = Teacher::count();
            $totalFamilies = Family::count();
            
            // For active counts, adjust based on your actual schema
            // If you don't have 'status' column, remove these or adjust
            $activeStudents = $totalStudents; // Temporary fallback
            $activeTeachers = $totalTeachers; // Temporary fallback
            
            // Try to get active counts if status column exists
            if (Schema::hasColumn('students', 'status')) {
                $activeStudents = Student::where('status', 'active')->count();
            }
            if (Schema::hasColumn('teachers', 'status')) {
                $activeTeachers = Teacher::where('status', 'active')->count();
            }
            
            // Today's attendance
            $today = now()->format('Y-m-d');
            $presentToday = Attendance::whereDate('date', $today)->where('status', 'present')->count();
            $absentToday = Attendance::whereDate('date', $today)->where('status', 'absent')->count();
            $lateToday = Attendance::whereDate('date', $today)->where('status', 'late')->count();
            
            $totalMarkedToday = $presentToday + $absentToday + $lateToday;
            $attendancePercentage = $totalMarkedToday > 0
                ? round(($presentToday / $totalMarkedToday) * 100)
                : 0;
            
            // Attendance trend for last 7 days
            $attendanceTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $presentCount = Attendance::whereDate('date', $date)
                    ->where('status', 'present')
                    ->count();
                $attendanceTrend[] = $presentCount;
            }
            
            // Student distribution by class - Simplified query
            $classDistribution = [];
            try {
                // Try to get classes with student counts
                $classes = Classes::withCount('students')->get();
                foreach ($classes as $class) {
                    $classDistribution[] = [
                        'class_name' => $class->name,
                        'count' => $class->students_count
                    ];
                }
            } catch (\Exception $e) {
                // If relationship doesn't exist, provide empty data
                Log::warning('Could not fetch class distribution: ' . $e->getMessage());
                $classDistribution = [];
            }
            
            // If no classes found, provide sample structure (empty)
            if (empty($classDistribution)) {
                $classDistribution = [];
            }
            
            // Monthly attendance for last 4 months
            $monthlyAttendance = [];
            for ($i = 3; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthName = $month->format('M');
                $startDate = $month->copy()->startOfMonth();
                $endDate = $month->copy()->endOfMonth();
                
                $present = Attendance::whereBetween('date', [$startDate, $endDate])
                    ->where('status', 'present')
                    ->count();
                $absent = Attendance::whereBetween('date', [$startDate, $endDate])
                    ->where('status', 'absent')
                    ->count();
                $late = Attendance::whereBetween('date', [$startDate, $endDate])
                    ->where('status', 'late')
                    ->count();
                
                $monthlyAttendance[] = [
                    'month' => $monthName,
                    'present' => $present,
                    'absent' => $absent,
                    'late' => $late
                ];
            }
            
            // Recent students - Simplified to avoid relationship errors
            $recentStudents = [];
            try {
                $students = Student::latest()->take(5)->get();
                foreach ($students as $student) {
                    $recentStudents[] = [
                        'id' => $student->id,
                        'user' => ['name' => $student->name ?? 'N/A'], // Adjust based on your schema
                        'admission_number' => $student->admission_number ?? 'N/A',
                        'currentClass' => $student->class_name ?? 'N/A', // Adjust based on your schema
                        'created_at' => $student->created_at
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch recent students: ' . $e->getMessage());
                $recentStudents = [];
            }
            
            // Recent teachers - Simplified
            $recentTeachers = [];
            try {
                $teachers = Teacher::latest()->take(5)->get();
                foreach ($teachers as $teacher) {
                    $recentTeachers[] = [
                        'id' => $teacher->id,
                        'user' => ['name' => $teacher->name ?? 'N/A'], // Adjust based on your schema
                        'qualification' => $teacher->qualification ?? 'N/A',
                        'created_at' => $teacher->created_at
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch recent teachers: ' . $e->getMessage());
                $recentTeachers = [];
            }
            
            // Prepare response
            $responseData = [
                'success' => true,
                'data' => [
                    'total_students' => $totalStudents,
                    'active_students' => $activeStudents,
                    'total_teachers' => $totalTeachers,
                    'active_teachers' => $activeTeachers,
                    'total_families' => $totalFamilies,
                    'attendance_percentage' => $attendancePercentage,
                    'present_today' => $presentToday,
                    'absent_today' => $absentToday,
                    'late_today' => $lateToday,
                    'attendance_trend' => $attendanceTrend,
                    'class_distribution' => $classDistribution,
                    'monthly_attendance' => $monthlyAttendance,
                    'recent_students' => $recentStudents,
                    'recent_teachers' => $recentTeachers
                ]
            ];
            
            // Log successful response (for debugging)
            Log::info('Dashboard data fetched successfully', ['stats' => $responseData['data']]);
            
            return response()->json($responseData);
            
        } catch (\Exception $e) {
            // Log the full error for debugging
            Log::error('Dashboard error: ' . $e->getMessage());
            Log::error('Dashboard error trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data: ' . $e->getMessage(),
                'error' => $e->getMessage() // Include error message for debugging
            ], 500);
        }
    }
}