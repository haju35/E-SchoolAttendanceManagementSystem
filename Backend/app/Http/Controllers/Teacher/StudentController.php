<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TeacherAssignment;
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
            ->where('class_id', $student->current_class_id)
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
            ->where('class_id', $student->current_class_id)
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
}