<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\Subject;

class TeacherRoleController extends Controller
{
    // Get teacher details with both homeroom and subject assignments
    public function show($id)
    {
        $teacher = Teacher::with([
            'user',
            'assignedClass',
            'assignedSection',
            'assignments.subject',
            'assignments.classRoom',
            'assignments.section'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'teacher' => $teacher,
                'is_homeroom' => $teacher->is_class_teacher,
                'homeroom_details' => $teacher->is_class_teacher ? [
                    'class' => $teacher->assignedClass,
                    'section' => $teacher->assignedSection
                ] : null,
                'subject_assignments' => $teacher->assignments
            ]
        ]);
    }
    
    // Assign teacher as homeroom (they can still be subject teacher)
    public function assignHomeroom(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'class_id' => 'required|exists:class_rooms,id',
            'section_id' => 'required|exists:sections,id',
        ]);
        
        $teacher = Teacher::findOrFail($request->teacher_id);
        
        // Teacher can be homeroom even if they already teach subjects
        // Just check if they are already homeroom for another class
        if ($teacher->is_class_teacher && $teacher->assigned_class_id != $request->class_id) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher is already a homeroom teacher for another class. Remove that assignment first.'
            ], 422);
        }
        
        // Check if class already has a homeroom teacher
        $existingHomeroom = Teacher::where('is_class_teacher', true)
            ->where('assigned_class_id', $request->class_id)
            ->where('assigned_section_id', $request->section_id)
            ->where('id', '!=', $teacher->id)
            ->exists();
            
        if ($existingHomeroom) {
            return response()->json([
                'success' => false,
                'message' => 'This class already has a homeroom teacher assigned.'
            ], 422);
        }
        
        // Assign as homeroom (preserves subject assignments)
        $teacher->is_class_teacher = true;
        $teacher->assigned_class_id = $request->class_id;
        $teacher->assigned_section_id = $request->section_id;
        $teacher->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Teacher assigned as homeroom successfully. Teacher can still teach subjects.',
            'data' => [
                'teacher' => $teacher->load(['assignedClass', 'assignedSection', 'assignments'])
            ]
        ]);
    }
    
    // Remove homeroom assignment (teacher remains subject teacher)
    public function removeHomeroom($id)
    {
        $teacher = Teacher::findOrFail($id);
        
        $teacher->is_class_teacher = false;
        $teacher->assigned_class_id = null;
        $teacher->assigned_section_id = null;
        $teacher->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Homeroom assignment removed. Teacher remains as subject teacher.',
            'data' => $teacher->load(['assignments'])
        ]);
    }
    
    // Assign subject to teacher (they can be homeroom or not)
    public function assignSubject(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:class_rooms,id',
            'section_id' => 'required|exists:sections,id',
            'academic_year' => 'required|string',
        ]);
        
        $teacher = Teacher::findOrFail($request->teacher_id);
        
        // Check for duplicate assignment
        $exists = TeacherAssignment::where('teacher_id', $request->teacher_id)
            ->where('subject_id', $request->subject_id)
            ->where('class_room_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('academic_year', $request->academic_year)
            ->exists();
            
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher already assigned to this subject for this class/section'
            ], 422);
        }
        
        $assignment = TeacherAssignment::create([
            'teacher_id' => $request->teacher_id,
            'subject_id' => $request->subject_id,
            'class_room_id' => $request->class_id,
            'section_id' => $request->section_id,
            'academic_year' => $request->academic_year,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subject assigned successfully',
            'data' => $assignment->load(['subject', 'classRoom', 'section'])
        ]);
    }
    
    // Get all teachers with their roles
    public function getAllTeachersWithRoles()
    {
        $teachers = Teacher::with([
            'user',
            'assignedClass',
            'assignedSection',
            'assignments.subject',
            'assignments.classRoom',
            'assignments.section'
        ])->get();
        
        $result = $teachers->map(function($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->user->name,
                'email' => $teacher->user->email,
                'is_homeroom' => $teacher->is_class_teacher,
                'homeroom_class' => $teacher->assignedClass?->name,
                'homeroom_section' => $teacher->assignedSection?->name,
                'subjects_taught' => $teacher->assignments->map(function($a) {
                    return $a->subject->name . ' (' . $a->classRoom->name . ' - ' . $a->section->name . ')';
                })->toArray(),
                'total_subjects' => $teacher->assignments->count()
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}