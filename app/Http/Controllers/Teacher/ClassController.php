<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherAssignment;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\Attendance;

class ClassController extends Controller
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

        // Get all assignments with relationships
        $assignments = TeacherAssignment::with(['classRoom', 'section', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        if ($assignments->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No classes assigned yet'
            ]);
        }

        $groupedClasses = [];

        foreach ($assignments as $assignment) {
            if (!$assignment->classRoom || !$assignment->section) {
                continue;
            }

            $classId = $assignment->classRoom->id;
            $sectionId = $assignment->section->id;

            // Check if class already exists
            if (!isset($groupedClasses[$classId])) {
                $groupedClasses[$classId] = [
                    'class' => [
                        'id' => $assignment->classRoom->id,
                        'name' => $assignment->classRoom->name,
                        'numeric_value' => $assignment->classRoom->numeric_value,
                    ],
                    'sections' => []
                ];
            }

            // Check if section already exists
            if (!isset($groupedClasses[$classId]['sections'][$sectionId])) {
                $groupedClasses[$classId]['sections'][$sectionId] = [
                    'section' => [
                        'id' => $assignment->section->id,
                        'name' => $assignment->section->name,
                        'capacity' => $assignment->section->capacity,
                    ],
                    'subjects' => []
                ];
            }

            // Add subject if not exists
            $subjectId = $assignment->subject->id ?? null;
            if ($subjectId && !in_array($subjectId, array_column($groupedClasses[$classId]['sections'][$sectionId]['subjects'], 'id'))) {
                $groupedClasses[$classId]['sections'][$sectionId]['subjects'][] = [
                    'id' => $assignment->subject->id,
                    'name' => $assignment->subject->name,
                    'code' => $assignment->subject->code
                ];
            }
        }

        // Convert sections from keyed array to values
        foreach ($groupedClasses as &$class) {
            $class['sections'] = array_values($class['sections']);
        }

        return response()->json([
            'success' => true,
            'data' => array_values($groupedClasses)
        ]);
    }

    public function students(Request $request, $id)
    {
        $teacher = $request->user()->teacher;
        $classId = $id;

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher profile not found'
            ], 404);
        }

        $request->validate([
            'section_id' => 'required|exists:sections,id'
        ]);

        // Verify teacher has access to this class/section - FIX: use class_room_id
        $hasAccess = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('class_room_id', $classId)  // Changed from class_id to class_room_id
            ->where('section_id', $request->section_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this class'
            ], 403);
        }

        // Get students with their attendance summary
        $students = Student::with(['user', 'currentClass', 'currentSection'])
            ->where('current_class_id', $classId)
            ->where('current_section_id', $request->section_id)
            ->orderBy('roll_number')
            ->get()
            ->map(function($student) use ($teacher) {
                // Get today's attendance for this student
                $todayAttendance = Attendance::where('student_id', $student->id)
                    ->where('teacher_id', $teacher->id)
                    ->whereDate('date', today())
                    ->first();
                
                // Get monthly attendance summary
                $monthlyAttendance = Attendance::where('student_id', $student->id)
                    ->where('teacher_id', $teacher->id)
                    ->whereMonth('date', now()->month)
                    ->get();
                
                return [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'email' => $student->user->email,
                    'roll_number' => $student->roll_number,
                    'admission_number' => $student->admission_number,
                    'phone' => $student->user->phone,
                    'address' => $student->user->address,
                    'today_status' => $todayAttendance ? $todayAttendance->status : 'not_marked',
                    'monthly_summary' => [
                        'total' => $monthlyAttendance->count(),
                        'present' => $monthlyAttendance->where('status', 'present')->count(),
                        'absent' => $monthlyAttendance->where('status', 'absent')->count(),
                        'late' => $monthlyAttendance->where('status', 'late')->count(),
                    ]
                ];
            });

        // Get class and section info
        $classInfo = null;
        $sectionInfo = null;
        
        $assignment = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('class_room_id', $classId)  // Changed from class_id to class_room_id
            ->where('section_id', $request->section_id)
            ->with(['classRoom', 'section'])
            ->first();
            
        if ($assignment) {
            $classInfo = $assignment->classRoom;
            $sectionInfo = $assignment->section;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'class' => $classInfo,
                'section' => $sectionInfo,
                'total_students' => $students->count(),
                'students' => $students
            ]
        ]);
    }
}