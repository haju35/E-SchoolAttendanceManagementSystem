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
        
        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
        
        $data = [
            'student' => $student,
            'class' => $student->currentClass,
            'section' => $student->currentSection,
            'attendance_summary' => [
                'total_days' => $totalDays,
                'present' => $presentDays,
                'absent' => $absentDays,
                'late' => $lateDays,
                'percentage' => $attendancePercentage
            ],
            'recent_attendance' => Attendance::where('student_id', $student->id)
                ->with(['subject', 'teacher.user'])
                ->latest()
                ->take(10)
                ->get()
        ];
        
        return view('student.dashboard', compact('data'));
    }
}