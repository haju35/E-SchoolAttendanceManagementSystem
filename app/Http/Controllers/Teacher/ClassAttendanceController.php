<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\ClassAttendance;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\DB;

class ClassAttendanceController extends Controller
{
    /**
     * Get class teacher dashboard with homeroom info
     */
    public function classTeacherDashboard()
    {
        try {
            $teacher = Auth::user()->teacher;
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found'
                ], 404);
            }
            
            $isClassTeacher = $teacher->is_class_teacher ?? false;
            $classTeacherInfo = null;
            
            if ($isClassTeacher && $teacher->assigned_class_id && $teacher->assigned_section_id) {
                // Get class info
                $classRoom = $teacher->assignedClass;
                $section = $teacher->assignedSection;
                
                // Get students with attendance summary
                $studentsList = Student::where('current_class_id', $teacher->assigned_class_id)
                    ->where('current_section_id', $teacher->assigned_section_id)
                    ->with('user')
                    ->get();
                
                $students = [];
                foreach ($studentsList as $student) {
                    $currentMonth = now()->month;
                    $currentYear = now()->year;
                    
                    $attendanceStats = ClassAttendance::where('student_id', $student->id)
                        ->whereMonth('date', $currentMonth)
                        ->whereYear('date', $currentYear)
                        ->selectRaw('
                            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent,
                            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late,
                            COUNT(*) as total
                        ')
                        ->first();
                    
                    $total = $attendanceStats->total ?? 0;
                    $present = $attendanceStats->present ?? 0;
                    $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
                    
                    $students[] = [
                        'id' => $student->id,
                        'user' => $student->user,
                        'roll_number' => $student->roll_number,
                        'admission_number' => $student->admission_number,
                        'present_count' => $attendanceStats->present ?? 0,
                        'absent_count' => $attendanceStats->absent ?? 0,
                        'late_count' => $attendanceStats->late ?? 0,
                        'attendance_percentage' => $percentage
                    ];
                }
                
                // Check if today's attendance is marked
                $todayAttendanceMarked = ClassAttendance::where('class_room_id', $teacher->assigned_class_id)
                    ->where('section_id', $teacher->assigned_section_id)
                    ->whereDate('date', today())
                    ->exists();
                
                $classTeacherInfo = [
                    'class_id' => $teacher->assigned_class_id,
                    'class_name' => $classRoom->name ?? 'N/A',
                    'section_id' => $teacher->assigned_section_id,
                    'section_name' => $section->name ?? 'N/A',
                    'total_students' => $studentsList->count(),
                    'students' => $students,
                    'today_attendance_marked' => $todayAttendanceMarked
                ];
            }
            
            // Get subject assignments for regular attendance
            $assignments = TeacherAssignment::with(['classRoom', 'section', 'subject'])
                ->where('teacher_id', $teacher->id)
                ->get();
            
            $subjectAssignments = [];
            foreach ($assignments as $assignment) {
                $subjectAssignments[] = [
                    'assignment_id' => $assignment->id,
                    'subject_id' => $assignment->subject_id,
                    'subject_name' => $assignment->subject->name,
                    'subject_code' => $assignment->subject->code,
                    'class_id' => $assignment->class_room_id,
                    'class_name' => $assignment->classRoom->name,
                    'section_id' => $assignment->section_id,
                    'section_name' => $assignment->section->name
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'is_class_teacher' => $isClassTeacher,
                    'class_teacher_info' => $classTeacherInfo,
                    'subject_assignments' => $subjectAssignments
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mark daily class attendance (for homeroom teachers)
     */
    public function markClassAttendance(Request $request)
    {
        try {
            $teacher = Auth::user()->teacher;
            
            if (!$teacher || !$teacher->is_class_teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only class teachers can mark daily attendance'
                ], 403);
            }
            
            $request->validate([
                'date' => 'required|date',
                'attendance' => 'required|array',
                'attendance.*.student_id' => 'required|exists:students,id',
                'attendance.*.status' => 'required|in:present,absent,late',
                'attendance.*.remarks' => 'nullable|string'
            ]);
            
            $date = $request->date;
            $classId = $teacher->assigned_class_id;
            $sectionId = $teacher->assigned_section_id;
            
            // Check if attendance already marked
            $existing = ClassAttendance::where('class_room_id', $classId)
                ->where('section_id', $sectionId)
                ->whereDate('date', $date)
                ->exists();
            
            if ($existing && !$request->edit_mode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance already marked for this date'
                ], 422);
            }
            
            DB::beginTransaction();
            
            // If edit mode, delete existing records first
            if ($request->edit_mode) {
                ClassAttendance::where('class_room_id', $classId)
                    ->where('section_id', $sectionId)
                    ->whereDate('date', $date)
                    ->delete();
            }
            
            foreach ($request->attendance as $record) {
                ClassAttendance::create([
                    'student_id' => $record['student_id'],
                    'class_room_id' => $classId,
                    'section_id' => $sectionId,
                    'date' => $date,
                    'status' => $record['status'],
                    'marked_by' => $teacher->id,
                    'remarks' => $record['remarks'] ?? null
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $request->edit_mode ? 'Class attendance updated successfully' : 'Class attendance marked successfully',
                'data' => [
                    'date' => $date,
                    'total_students' => count($request->attendance)
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get class attendance for a specific date
     */
    public function getClassAttendance(Request $request)
    {
        try {
            $teacher = Auth::user()->teacher;
            
            if (!$teacher || !$teacher->is_class_teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            $request->validate([
                'date' => 'required|date'
            ]);
            
            $attendance = ClassAttendance::where('class_room_id', $teacher->assigned_class_id)
                ->where('section_id', $teacher->assigned_section_id)
                ->whereDate('date', $request->date)
                ->with('student.user')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $request->date,
                    'attendance' => $attendance
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get attendance: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get student's class attendance summary
     */
    public function getStudentClassAttendance($studentId)
    {
        try {
            $attendance = ClassAttendance::where('student_id', $studentId)
                ->orderBy('date', 'desc')
                ->get();
            
            $summary = [
                'total_days' => $attendance->count(),
                'present' => $attendance->where('status', 'present')->count(),
                'absent' => $attendance->where('status', 'absent')->count(),
                'late' => $attendance->where('status', 'late')->count(),
                'attendance_percentage' => $attendance->count() > 0 
                    ? round(($attendance->where('status', 'present')->count() / $attendance->count()) * 100, 2)
                    : 0
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'records' => $attendance,
                    'summary' => $summary
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get student attendance: ' . $e->getMessage()
            ], 500);
        }
    }
}