<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function attendance(Request $request)
    {
        $teacher = $request->user()->teacher;

        $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date'
        ]);

        // Verify teacher has access - FIX: use class_room_id
        $hasAccess = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('class_room_id', $request->class_room_id)  // Changed from class_id to class_room_id
            ->where('section_id', $request->section_id)
            ->when($request->subject_id, function($query, $subjectId) {
                $query->where('subject_id', $subjectId);
            })
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this class'
            ], 403);
        }

        $query = Attendance::with(['student.user', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->where('class_room_id', $request->class_room_id)  // Changed from class_id to class_room_id
            ->where('section_id', $request->section_id)
            ->whereBetween('date', [$request->from_date, $request->to_date]);

        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        $attendances = $query->get();

        // Generate report
        $report = [
            'period' => [
                'from' => $request->from_date,
                'to' => $request->to_date
            ],
            'class' => [
                'id' => $request->class_room_id,
                'section_id' => $request->section_id
            ],
            'summary' => [
                'total_records' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count()
            ],
            'daily_breakdown' => $attendances->groupBy('date')->map(function($items, $date) {
                return [
                    'date' => $date,
                    'total' => $items->count(),
                    'present' => $items->where('status', 'present')->count(),
                    'absent' => $items->where('status', 'absent')->count(),
                    'late' => $items->where('status', 'late')->count()
                ];
            })->values(),
            'student_wise' => $attendances->groupBy('student_id')->map(function($items) {
                $student = $items->first()->student;
                $total = $items->count();
                $present = $items->where('status', 'present')->count();
                return [
                    'student_id' => $student->id,
                    'name' => $student->user->name,
                    'roll_number' => $student->roll_number,
                    'total_classes' => $total,
                    'present' => $present,
                    'absent' => $items->where('status', 'absent')->count(),
                    'late' => $items->where('status', 'late')->count(),
                    'percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0
                ];
            })->values()
        ];

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }
}