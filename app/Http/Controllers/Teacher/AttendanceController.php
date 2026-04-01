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
                        'reason' => $data['reason'] ?? null
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
}