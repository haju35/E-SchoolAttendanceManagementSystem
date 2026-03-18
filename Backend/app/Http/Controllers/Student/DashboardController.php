<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found'
            ], 404);
        }

        // Get today's date
        $today = Carbon::today(); // or today()
        
        // Total attendance stats
        $totalDays = Attendance::where('student_id', $student->id)->count();
        $presentDays = Attendance::where('student_id', $student->id)
            ->where('status', 'present')->count();
        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
        
        // FIXED: Recent attendance - using Query Builder style
        $recentAttendance = Attendance::with(['subject', 'teacher.user'])
            ->where('student_id', $student->id)
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();
        
        // FIXED: Today's status - using Query Builder style
        $todayAttendance = Attendance::where('student_id', $student->id)
            ->whereDate('date', $today)  // CORRECT: 2 parameters - column, value
            ->first();
        
        // FIXED: Monthly summary - using Query Builder style with whereBetween
        $startOfMonth = Carbon::today()->startOfMonth();
        $endOfMonth = Carbon::today()->endOfMonth();
        
        $monthlyAttendance = Attendance::where('student_id', $student->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])  // CORRECT: using whereBetween for ranges
            ->get();
        
        // If you need to filter a collection (already fetched from DB)
        $allAttendances = Attendance::where('student_id', $student->id)->get();
        // FIXED: Filter collection - using Collection's where method
        $filteredAttendances = $allAttendances->where('status', 'present'); // NOT whereDate!
        
        $data = [
            'student' => $student->load(['user', 'currentClass', 'currentSection']),
            'attendance_summary' => [
                'total_days' => $totalDays,
                'present' => $presentDays,
                'absent' => Attendance::where('student_id', $student->id)
                    ->where('status', 'absent')->count(),
                'late' => Attendance::where('student_id', $student->id)
                    ->where('status', 'late')->count(),
                'percentage' => $attendancePercentage
            ],
            'recent_attendance' => $recentAttendance,
            'today_status' => $todayAttendance ? $todayAttendance->status : 'No attendance marked',
            'monthly_summary' => [
                'total' => $monthlyAttendance->count(),
                'present' => $monthlyAttendance->where('status', 'present')->count(),
                'absent' => $monthlyAttendance->where('status', 'absent')->count(),
                'late' => $monthlyAttendance->where('status', 'late')->count(),
            ]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}