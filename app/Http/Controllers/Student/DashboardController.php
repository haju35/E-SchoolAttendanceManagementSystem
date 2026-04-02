<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;

class DashboardController extends Controller
{
    public function index()
    {
            $student = Auth::user()->student;
            $student->load('currentClass', 'currentSection');

            $totalDays = Attendance::where('student_id', $student->id)->count();
            $presentDays = Attendance::where('student_id', $student->id)
                ->where('status', 'present')->count();
            $absentDays = Attendance::where('student_id', $student->id)
                ->where('status', 'absent')->count();
            $lateDays = Attendance::where('student_id', $student->id)
                ->where('status', 'late')->count();

            $attendancePercentage = $totalDays > 0 
                ? round(($presentDays / $totalDays) * 100, 2) 
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'student_name' => $student->user->name,
                    'class_name' => $student->currentClass->name ?? '',
                    'section_name' => $student->currentSection->name ?? '',
                    'roll_no' => $student->roll_number ?? '',
                    
                    'stats' => [
                        'attendance_rate' => $attendancePercentage,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'late_days' => $lateDays
                    ],

                    'recent_attendance' => Attendance::where('student_id', $student->id)
                        ->with('subject')
                        ->latest()
                        ->take(10)
                        ->get()
                ]
            ]);
    }
}