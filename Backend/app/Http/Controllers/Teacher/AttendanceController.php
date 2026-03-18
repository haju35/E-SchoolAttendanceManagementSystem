<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;           // ← THIS WAS MISSING
use App\Models\Student;               // ← Add this
use App\Models\TeacherAssignment;     // ← Add this
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
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

        $request->validate([
            'date' => 'sometimes|date',
            'class_id' => 'nullable|exists:class_rooms,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id'
        ]);

        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $query = Attendance::with(['student.user', 'class', 'section', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->whereDate('date', $date);  // ✅ CORRECT: 2 parameters

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        $attendances = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->format('Y-m-d'),
                'total' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'records' => $attendances->map(function($attendance) {
                    return [
                        'id' => $attendance->id,
                        'student_name' => $attendance->student->user->name ?? 'N/A',
                        'roll_number' => $attendance->student->roll_number ?? 'N/A',
                        'status' => $attendance->status,
                        'reason' => $attendance->reason,
                        'subject' => $attendance->subject->name ?? 'N/A',
                        'marked_at' => $attendance->marked_at ? $attendance->marked_at->format('H:i:s') : null
                    ];
                })
            ]
        ]);
    }

    public function store(Request $request)
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher profile not found'
            ], 404);
        }

        $request->validate([
            'class_id' => 'required|exists:class_rooms,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'date' => 'required|date',
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:present,absent,late',
            'attendances.*.reason' => 'nullable|string'
        ]);

        // Verify teacher is assigned to this class/subject
        $assignment = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('subject_id', $request->subject_id)
            ->where('academic_year', Carbon::now()->year)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this class/subject'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $created = [];
            
            foreach ($request->attendances as $attendanceData) {
                // Check if attendance already exists for this student/subject/date
                $attendance = Attendance::updateOrCreate(
                    [
                        'student_id' => $attendanceData['student_id'],
                        'subject_id' => $request->subject_id,
                        'date' => $request->date
                    ],
                    [
                        'class_id' => $request->class_id,
                        'section_id' => $request->section_id,
                        'teacher_id' => $teacher->id,
                        'status' => $attendanceData['status'],
                        'reason' => $attendanceData['reason'] ?? null,
                        'marked_by' => $request->user()->id,
                        'marked_at' => Carbon::now()
                    ]
                );
                
                $created[] = $attendance;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'data' => [
                    'count' => count($created),
                    'attendances' => collect($created)->map(function($a) {
                        return [
                            'id' => $a->id,
                            'student_id' => $a->student_id,
                            'status' => $a->status,
                            'date' => $a->date
                        ];
                    })
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $attendance = Attendance::with([
            'student.user', 
            'class', 
            'section', 
            'subject', 
            'teacher.user',
            'markedBy'
        ])->find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    public function update(Request $request, $id)
    {
        $teacher = $request->user()->teacher;
        
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher profile not found'
            ], 404);
        }

        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found'
            ], 404);
        }

        // Verify this attendance belongs to this teacher
        if ($attendance->teacher_id !== $teacher->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update your own attendance records'
            ], 403);
        }

        $request->validate([
            'status' => 'sometimes|in:present,absent,late',
            'reason' => 'nullable|string'
        ]);

        $attendance->update([
            'status' => $request->status ?? $attendance->status,
            'reason' => $request->reason ?? $attendance->reason,
            'marked_by' => $request->user()->id,
            'marked_at' => Carbon::now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully',
            'data' => $attendance->fresh([
                'student.user', 'class', 'section', 'subject', 'teacher.user'
            ])
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $teacher = $request->user()->teacher;
        
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher profile not found'
            ], 404);
        }

        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found'
            ], 404);
        }

        // Verify this attendance belongs to this teacher
        if ($attendance->teacher_id !== $teacher->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own attendance records'
            ], 403);
        }

        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance record deleted successfully'
        ]);
    }
}