<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\ClassAttendance;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $teacher = Auth::user()->teacher;
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found'
                ], 404);
            }
            
            // Get teacher's assignments
            $assignments = TeacherAssignment::with(['classRoom', 'section', 'subject'])
                ->where('teacher_id', $teacher->id)
                ->get();
            
            $students = [];
            $date = $request->date ?? date('Y-m-d');
            
            if ($request->class_room_id && $request->section_id) {
                // Get students for the selected class and section
                $students = Student::with('user')
                    ->where('current_class_id', $request->class_room_id)
                    ->where('current_section_id', $request->section_id)
                    ->orderBy('roll_number')
                    ->get()
                    ->map(function($student) use ($date, $request) {
                        // Check if attendance already marked for this date and subject
                        $existingAttendance = Attendance::where('student_id', $student->id)
                            ->where('date', $date)
                            ->when($request->subject_id, function($query) use ($request) {
                                $query->where('subject_id', $request->subject_id);
                            })
                            ->first();
                        
                        return [
                            'id' => $student->id,
                            'name' => $student->user->name,
                            'roll_number' => $student->roll_number,
                            'admission_number' => $student->admission_number,
                            'status' => $existingAttendance ? $existingAttendance->status : 'present',
                            'reason' => $existingAttendance ? $existingAttendance->reason : null
                        ];
                    });
            }
            
            // Get unique classes for dropdown
            $uniqueClasses = $assignments->unique('class_room_id')->map(function($item) {
                return [
                    'id' => $item->classRoom->id,
                    'name' => $item->classRoom->name
                ];
            })->values();
            
            // Get subjects
            $subjects = $assignments->unique('subject_id')->map(function($item) {
                return [
                    'id' => $item->subject->id,
                    'name' => $item->subject->name,
                    'code' => $item->subject->code
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'class_rooms' => $uniqueClasses,
                    'students' => $students,
                    'subjects' => $subjects,
                    'date' => $date
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'class_room_id' => 'required|exists:class_rooms,id',
                'section_id' => 'required|exists:sections,id',
                'date' => 'required|date',
                'attendance' => 'required|array',
                'attendance.*.subject_id' => 'required|exists:subjects,id',
                'attendance.*.status' => 'required|in:present,absent,late'
            ]);
            
            $teacher = Auth::user()->teacher;
            
            DB::beginTransaction();
            
            foreach($request->attendance as $studentId => $data) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'date' => $request->date,
                        'subject_id' => $data['subject_id']
                    ],
                    [
                        'class_room_id' => $request->class_room_id,
                        'section_id' => $request->section_id,
                        'teacher_id' => $teacher->id,
                        'status' => $data['status'],
                        'reason' => $data['reason'] ?? null,
                        'marked_by' => Auth::id(),'marked_at' => now()
                    ]
                );
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully!'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
            
            $data = [
                'is_class_teacher' => $teacher->is_class_teacher,
                'class_teacher_info' => null,
                'subject_assignments' => []
            ];
            
            // If class teacher, get class info
            if ($teacher->is_class_teacher) {
                $students = $teacher->getClassTeacherStudents();
                
                // Check if today's attendance already marked
                $todayAttendanceMarked = ClassAttendance::where('class_room_id', $teacher->assigned_class_id)
                    ->where('section_id', $teacher->assigned_section_id)
                    ->whereDate('date', today())
                    ->exists();
                
                $data['class_teacher_info'] = [
                    'class_id' => $teacher->assigned_class_id,
                    'class_name' => $teacher->assignedClass->name,
                    'section_id' => $teacher->assigned_section_id,
                    'section_name' => $teacher->assignedSection->name,
                    'students' => $students,
                    'today_attendance_marked' => $todayAttendanceMarked,
                    'total_students' => $students->count()
                ];
            }
            
            // Get subject assignments (for your existing subject attendance)
            $assignments = TeacherAssignment::with(['classRoom', 'section', 'subject'])
                ->where('teacher_id', $teacher->id)
                ->get();
            
            foreach ($assignments as $assignment) {
                $data['subject_assignments'][] = [
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
                'data' => $data
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
     * Mark daily class attendance (Class Teacher only)
     */
    public function markClassAttendance(Request $request)
    {
        try {
            $teacher = Auth::user()->teacher;
            
            // Verify teacher is class teacher
            if (!$teacher || !$teacher->is_class_teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only class teachers can mark daily attendance'
                ], 403);
            }
            
            $validated = $request->validate([
                'date' => 'required|date',
                'attendance' => 'required|array',
                'attendance.*.student_id' => 'required|exists:students,id',
                'attendance.*.status' => 'required|in:present,absent,late',
                'attendance.*.remarks' => 'nullable|string'
            ]);
            
            $date = $request->date;
            $classId = $teacher->assigned_class_id;
            $sectionId = $teacher->assigned_section_id;
            
            // Check if attendance already marked for this date
            $existing = ClassAttendance::where('class_room_id', $classId)
                ->where('section_id', $sectionId)
                ->whereDate('date', $date)
                ->exists();
            
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class attendance already marked for this date'
                ], 422);
            }
            
            DB::beginTransaction();
            
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
                'message' => 'Class attendance marked successfully',
                'data' => [
                    'date' => $date,
                    'total_students' => count($request->attendance)
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark class attendance',
                'error' => $e->getMessage()
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
            
            // Get all students in class
            $allStudents = $teacher->getClassTeacherStudents();
            
            // Merge attendance data
            $result = $allStudents->map(function($student) use ($attendance) {
                $att = $attendance->where('student_id', $student->id)->first();
                return [
                    'student_id' => $student->id,
                    'student_name' => $student->user->name,
                    'roll_number' => $student->roll_number,
                    'status' => $att ? $att->status : 'not_marked',
                    'remarks' => $att ? $att->remarks : null,
                    'marked_at' => $att ? $att->created_at : null
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $request->date,
                    'attendance' => $result,
                    'summary' => [
                        'present' => $result->where('status', 'present')->count(),
                        'absent' => $result->where('status', 'absent')->count(),
                        'late' => $result->where('status', 'late')->count(),
                        'not_marked' => $result->where('status', 'not_marked')->count()
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get attendance',
                'error' => $e->getMessage()
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
                'message' => 'Failed to get student attendance'
            ], 500);
        }
    }
}