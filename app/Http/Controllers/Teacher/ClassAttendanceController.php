<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\ClassAttendance;
use App\Models\ClassRoom;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClassAttendanceController extends Controller
{
    // Get classes where teacher is class teacher
    public function getClassTeacherClasses()
    {
        try {
            $teacher = Auth::user()->teacher;
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found',
                    'data' => []
                ]);
            }
            
            if (!$teacher->is_class_teacher) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'You are not a class teacher'
                ]);
            }
            
            if (!$teacher->assigned_class_id) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No class assigned'
                ]);
            }
            
            // Get the class
            $class = ClassRoom::find($teacher->assigned_class_id);
            
            if (!$class) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            // Get sections for this class
            $sections = Section::where('class_room_id', $class->id)->get();
            
            $sectionsData = [];
            foreach ($sections as $section) {
                $studentCount = Student::where('current_class_id', $class->id)
                    ->where('current_section_id', $section->id)
                    ->count();
                    
                $sectionsData[] = [
                    'id' => $section->id,
                    'name' => $section->name,
                    'capacity' => $section->capacity,
                    'student_count' => $studentCount
                ];
            }
            
            $result = [
                [
                    'id' => $class->id,
                    'name' => $class->name,
                    'numeric_value' => $class->numeric_value,
                    'sections' => $sectionsData
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('getClassTeacherClasses error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
    
    // Get students for class attendance
    public function getClassStudents(Request $request)
    {
        try {
            $teacher = Auth::user()->teacher;
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 404);
            }
            
            $request->validate([
                'class_room_id' => 'required|exists:class_rooms,id',
                'section_id' => 'required|exists:sections,id',
                'date' => 'required|date'
            ]);
            
            $classId = $request->class_room_id;
            $sectionId = $request->section_id;
            $date = $request->date;
            
            // Get students using DB facade (avoid model issues)
            $students = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.id')
                ->where('students.current_class_id', $classId)
                ->where('students.current_section_id', $sectionId)
                ->select(
                    'students.id',
                    'students.roll_number',
                    'students.admission_number',
                    'users.name as student_name'
                )
                ->orderBy('students.roll_number', 'asc')
                ->get();
            
            // Check if attendance already marked
            $attendanceMarked = DB::table('class_attendances')
                ->where('class_room_id', $classId)
                ->where('section_id', $sectionId)
                ->where('date', $date)
                ->exists();
            
            // Get existing attendance records
            $existingAttendance = [];
            if ($attendanceMarked) {
                $records = DB::table('class_attendances')
                    ->where('class_room_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('date', $date)
                    ->get();
                
                foreach ($records as $record) {
                    $existingAttendance[$record->student_id] = $record;
                }
            }
            
            // Format students data
            $studentsData = [];
            foreach ($students as $student) {
                $attendance = isset($existingAttendance[$student->id]) ? $existingAttendance[$student->id] : null;
                $studentsData[] = [
                    'id' => $student->id,
                    'roll_number' => $student->roll_number ?? '-',
                    'admission_number' => $student->admission_number ?? '',
                    'name' => $student->student_name ?? 'Unknown',
                    'status' => $attendance ? $attendance->status : 'present',
                    'remarks' => $attendance ? $attendance->remarks : ''
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $studentsData,
                    'attendance_marked' => $attendanceMarked,
                    'total_students' => count($studentsData)
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('getClassStudents error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    // Mark class attendance
    public function markClassAttendance(Request $request)
    {
        try {
            $teacher = Auth::user()->teacher;
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 404);
            }
            
            $request->validate([
                'date' => 'required|date',
                'class_room_id' => 'required|exists:class_rooms,id',
                'section_id' => 'required|exists:sections,id',
                'attendance' => 'required|array',
                'attendance.*.student_id' => 'required|exists:students,id',
                'attendance.*.status' => 'required|in:present,absent,late'
            ]);
            
            $date = $request->date;
            $classId = $request->class_room_id;
            $sectionId = $request->section_id;
            
            // Delete existing records
            DB::table('class_attendances')
                ->where('class_room_id', $classId)
                ->where('section_id', $sectionId)
                ->where('date', $date)
                ->delete();
            
            // Insert new records
            foreach ($request->attendance as $record) {
                DB::table('class_attendances')->insert([
                    'student_id' => $record['student_id'],
                    'class_room_id' => $classId,
                    'section_id' => $sectionId,
                    'date' => $date,
                    'status' => $record['status'],
                    'marked_by' => $teacher->id,
                    'remarks' => $record['remarks'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('markClassAttendance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // Get class attendance for a date
    public function getClassAttendance(Request $request)
    {
        try {
            $teacher = Auth::user()->teacher;
            
            $request->validate([
                'date' => 'required|date'
            ]);
            
            $attendance = DB::table('class_attendances')
                ->where('class_room_id', $teacher->assigned_class_id)
                ->where('section_id', $teacher->assigned_section_id)
                ->where('date', $request->date)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'attendance' => $attendance
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // Dashboard method
    public function classTeacherDashboard()
    {
        try {
            $teacher = Auth::user()->teacher;
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 404);
            }
            
            $isClassTeacher = $teacher->is_class_teacher ?? false;
            $classTeacherInfo = null;
            
            // Log the values for debugging
            \Log::info('classTeacherDashboard called', [
                'teacher_id' => $teacher->id,
                'is_class_teacher' => $isClassTeacher,
                'assigned_class_id' => $teacher->assigned_class_id,
                'assigned_section_id' => $teacher->assigned_section_id
            ]);
            
            // If teacher is a class teacher, get the info
            if ($isClassTeacher && $teacher->assigned_class_id && $teacher->assigned_section_id) {
                $classRoom = \App\Models\ClassRoom::find($teacher->assigned_class_id);
                $section = \App\Models\Section::find($teacher->assigned_section_id);
                
                // Get students in this class
                $studentsList = \App\Models\Student::where('current_class_id', $teacher->assigned_class_id)
                    ->where('current_section_id', $teacher->assigned_section_id)
                    ->with('user')
                    ->get();
                
                $students = [];
                foreach ($studentsList as $student) {
                    $students[] = [
                        'id' => $student->id,
                        'user' => [
                            'name' => $student->user->name ?? 'Unknown'
                        ],
                        'roll_number' => $student->roll_number,
                        'admission_number' => $student->admission_number,
                        'present_count' => 0,
                        'absent_count' => 0,
                        'late_count' => 0,
                        'attendance_percentage' => 0
                    ];
                }
                
                // Check if today's attendance is marked
                $todayAttendanceMarked = \App\Models\ClassAttendance::where('class_room_id', $teacher->assigned_class_id)
                    ->where('section_id', $teacher->assigned_section_id)
                    ->whereDate('date', today())
                    ->exists();
                
                $classTeacherInfo = [
                    'class_room_id' => $teacher->assigned_class_id,
                    'class_name' => $classRoom->name ?? 'N/A',
                    'section_id' => $teacher->assigned_section_id,
                    'section_name' => $section->name ?? 'N/A',
                    'total_students' => $studentsList->count(),
                    'students' => $students,
                    'today_attendance_marked' => $todayAttendanceMarked
                ];
                
                \Log::info('classTeacherInfo created', ['info' => $classTeacherInfo]);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'is_class_teacher' => $isClassTeacher,
                    'class_teacher_info' => $classTeacherInfo
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('classTeacherDashboard error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}