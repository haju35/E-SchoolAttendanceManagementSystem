<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\TeacherAssignment;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $teacher = Auth::user()->teacher;
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found'
                ], 404);
            }
            
            // Get all assignments
            $assignments = TeacherAssignment::with(['class', 'section', 'subject'])
                ->where('teacher_id', $teacher->id)
                ->get();
            
            // Get unique classes
            $uniqueClasses = $assignments->unique('class_id');
            
            // Calculate total students
            $totalStudents = 0;
            foreach ($assignments->unique(function($item) {
                return $item->class_id . '-' . $item->section_id;
            }) as $assignment) {
                $totalStudents += $assignment->class->students()
                    ->where('current_section_id', $assignment->section_id)
                    ->count();
            }
            
            // Get today's attendance count
            $todayAttendance = Attendance::where('teacher_id', $teacher->id)
                ->whereDate('date', today())
                ->count();
            
            // Get subjects count
            $subjectsCount = $assignments->unique('subject_id')->count();
            
            // Prepare classes data for frontend
            $myClasses = [];
            foreach ($assignments->groupBy('class_id') as $classId => $classAssignments) {
                $class = $classAssignments->first()->class;
                $sections = [];
                
                foreach ($classAssignments->groupBy('section_id') as $sectionId => $sectionAssignments) {
                    $section = $sectionAssignments->first()->section;
                    $sections[] = [
                        'section' => [
                            'id' => $section->id,
                            'name' => $section->name,
                            'capacity' => $section->capacity
                        ],
                        'subjects' => $sectionAssignments->map(function($assignment) {
                            return [
                                'id' => $assignment->subject->id,
                                'name' => $assignment->subject->name,
                                'code' => $assignment->subject->code
                            ];
                        })->values()
                    ];
                }
                
                $myClasses[] = [
                    'class' => [
                        'id' => $class->id,
                        'name' => $class->name,
                        'numeric_value' => $class->numeric_value
                    ],
                    'sections' => $sections
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'my_classes' => count($uniqueClasses),
                    'total_students' => $totalStudents,
                    'today_attendance' => $todayAttendance,
                    'subjects_count' => $subjectsCount,
                    'classes' => $myClasses
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}