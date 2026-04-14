<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Debug log
            Log::info('Student Dashboard accessed', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role
            ]);
            
            // Check if student relationship exists
            if (!$user->student) {
                Log::error('No student record for user: ' . $user->id);
                return response()->json([
                    'success' => false,
                    'message' => 'Student profile not found. Please contact administrator.'
                ], 404);
            }
            
            $student = $user->student;
            
            // Log student data
            Log::info('Student data', [
                'student_id' => $student->id,
                'roll_number' => $student->roll_number
            ]);
            
            // Load relationships safely
            try {
                $student->load('currentClass', 'currentSection');
            } catch (\Exception $e) {
                Log::warning('Could not load class/section: ' . $e->getMessage());
            }
            
            // Get attendance
            $attendance = Attendance::where('student_id', $student->id)->get();
            
            $totalDays = $attendance->count();
            $presentDays = $attendance->where('status', 'present')->count();
            $absentDays = $attendance->where('status', 'absent')->count();
            $lateDays = $attendance->where('status', 'late')->count();
            
            $attendancePercentage = $totalDays > 0 
                ? round(($presentDays / $totalDays) * 100, 2) 
                : 0;
            
            // Get recent attendance with subject
            $recentAttendance = Attendance::where('student_id', $student->id)
                ->with('subject')
                ->latest()
                ->take(10)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'student_name' => $user->name,
                    'class_name' => $student->currentClass ? $student->currentClass->name : 'Not Assigned',
                    'section_name' => $student->currentSection ? $student->currentSection->name : 'Not Assigned',
                    'roll_no' => $student->roll_number ?? 'Not Assigned',
                    'stats' => [
                        'attendance_rate' => $attendancePercentage,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'late_days' => $lateDays
                    ],
                    'recent_attendance' => $recentAttendance
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Dashboard Error: ' . $e->getMessage());
            Log::error('Line: ' . $e->getLine());
            Log::error('File: ' . $e->getFile());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}