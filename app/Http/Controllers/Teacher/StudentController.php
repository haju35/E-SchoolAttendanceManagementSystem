<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\ClassAttendance;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function show(Request $request, $id)
    {
        $teacher = $request->user()->teacher;

        $student = Student::with([
            'user', 
            'currentClass', 
            'currentSection',
            'family.user',
            'attendances' => function($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                  ->with('subject')
                  ->latest()
                  ->take(20);
            }
        ])->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        // Check if teacher has access to this student's class
        $hasAccess = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('class_room_id', $student->current_class_id)
            ->where('section_id', $student->current_section_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this student'
            ], 403);
        }

        // Calculate attendance statistics for subjects this teacher teaches
        $subjectIds = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('class_room_id', $student->current_class_id)
            ->where('section_id', $student->current_section_id)
            ->pluck('subject_id');

        $attendances = $student->attendances()
            ->whereIn('subject_id', $subjectIds)
            ->get();

        $statistics = [
            'total_classes' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'percentage' => $attendances->count() > 0 
                ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 2) 
                : 0,
            'subject_wise' => $attendances->groupBy('subject_id')->map(function($items) {
                return [
                    'subject' => $items->first()->subject->name ?? 'N/A',
                    'total' => $items->count(),
                    'present' => $items->where('status', 'present')->count(),
                    'percentage' => round(($items->where('status', 'present')->count() / $items->count()) * 100, 2)
                ];
            })->values()
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $student,
                'attendance_statistics' => $statistics
            ]
        ]);
    }

    public function getStudentAttendance(Request $request, $id)
    {
        try {
            $teacher = $request->user()->teacher;
            $student = Student::with(['user', 'currentClass', 'currentSection'])->find($id);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }
            
            // Get attendance records for this student
            $attendances = ClassAttendance::where('student_id', $id)
                ->with(['classRoom', 'section'])
                ->orderBy('date', 'desc')
                ->get()
                ->map(function($attendance) {
                    return [
                        'id' => $attendance->id,
                        'date' => $attendance->date,
                        'status' => $attendance->status,
                        'remarks' => $attendance->remarks,
                        'class_name' => $attendance->classRoom->name ?? 'N/A',
                        'section_name' => $attendance->section->name ?? 'N/A'
                    ];
                });
            
            // Calculate statistics
            $total = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $absent = $attendances->where('status', 'absent')->count();
            $late = $attendances->where('status', 'late')->count();
            $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->user->name ?? 'Unknown',
                        'roll_number' => $student->roll_number,
                        'admission_number' => $student->admission_number,
                        'class_name' => $student->currentClass->name ?? 'N/A',
                        'section_name' => $student->currentSection->name ?? 'N/A'
                    ],
                    'attendance_records' => $attendances,
                    'statistics' => [
                        'total_days' => $total,
                        'present' => $present,
                        'absent' => $absent,
                        'late' => $late,
                        'percentage' => $percentage
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('getStudentAttendance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}