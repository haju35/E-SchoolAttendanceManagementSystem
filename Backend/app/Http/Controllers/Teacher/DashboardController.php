<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TeacherAssignment;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;
        
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher profile not found'
            ], 404);
        }

        // Get teacher's assignments
        $assignments = TeacherAssignment::where('teacher_id', $teacher->id)->get();
        
        // Calculate statistics using Collection methods
        $stats = [
            'total_classes' => $assignments->pluck('class_id')->unique()->count(),
            'total_sections' => $assignments->pluck('section_id')->unique()->count(),
            'total_subjects' => $assignments->pluck('subject_id')->unique()->count(),
            
            // Using Query Builder with whereDate (CORRECT)
            'today_attendance' => Attendance::where('teacher_id', $teacher->id)
                ->whereDate('date', Carbon::today())  // ✅ CORRECT: 2 parameters
                ->count(),
            
            // Recent attendances - using Query Builder
            'recent_attendances' => Attendance::with(['student.user', 'class', 'section'])
                ->where('teacher_id', $teacher->id)
                ->latest('date')
                ->limit(10)
                ->get()
                ->map(function($attendance) {
                    return [
                        'id' => $attendance->id,
                        'date' => $attendance->date->format('Y-m-d'),
                        'student_name' => $attendance->student->user->name ?? 'N/A',
                        'class' => $attendance->class->name ?? 'N/A',
                        'section' => $attendance->section->name ?? 'N/A',
                        'status' => $attendance->status,
                        'time' => $attendance->created_at->format('H:i:s')
                    ];
                }),
            
            // Additional stats for better dashboard
            'total_students' => Student::whereIn('current_class_id', $assignments->pluck('class_id'))
                ->whereIn('current_section_id', $assignments->pluck('section_id'))
                ->count(),
            
            'this_week_attendance' => Attendance::where('teacher_id', $teacher->id)
                ->whereBetween('date', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
                ->count(),
            
            'attendance_by_status' => [
                'present' => Attendance::where('teacher_id', $teacher->id)
                    ->whereDate('date', Carbon::today())
                    ->where('status', 'present')
                    ->count(),
                'absent' => Attendance::where('teacher_id', $teacher->id)
                    ->whereDate('date', Carbon::today())
                    ->where('status', 'absent')
                    ->count(),
                'late' => Attendance::where('teacher_id', $teacher->id)
                    ->whereDate('date', Carbon::today())
                    ->where('status', 'late')
                    ->count(),
            ]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}