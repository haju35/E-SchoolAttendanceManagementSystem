<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassTeacherController extends Controller
{
    // Get all teachers and classes for assignment
    public function index()
    {
        try {
            $teachers = Teacher::with('user')->get();
            $classes = ClassRoom::with('sections')->get();
            $classTeachers = Teacher::with(['user', 'assignedClass', 'assignedSection'])
                ->where('is_class_teacher', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'teachers' => $teachers,
                    'classes' => $classes,
                    'classTeachers' => $classTeachers
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load data: ' . $e->getMessage()
            ], 500);
        }
    }

    // Assign a teacher as class teacher
    public function assign(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'class_id' => 'required|exists:class_rooms,id',
            'section_id' => 'required|exists:sections,id'
        ]);

        try {
            DB::beginTransaction();

            // Remove class teacher status from any existing teacher for this class/section
            Teacher::where('assigned_class_id', $request->class_id)
                ->where('assigned_section_id', $request->section_id)
                ->update([
                    'is_class_teacher' => false,
                    'assigned_class_id' => null,
                    'assigned_section_id' => null
                ]);

            // Assign new class teacher
            $teacher = Teacher::findOrFail($request->teacher_id);
            $teacher->update([
                'is_class_teacher' => true,
                'assigned_class_id' => $request->class_id,
                'assigned_section_id' => $request->section_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class teacher assigned successfully',
                'data' => $teacher->load('user', 'assignedClass', 'assignedSection')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign class teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    // Remove class teacher status
    public function remove($teacherId)
    {
        try {
            $teacher = Teacher::findOrFail($teacherId);
            $teacher->update([
                'is_class_teacher' => false,
                'assigned_class_id' => null,
                'assigned_section_id' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Class teacher removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove class teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get all class teachers list (FIXED - returns proper structure)
    public function list()
    {
        try {
            $classTeachers = Teacher::with(['user', 'assignedClass', 'assignedSection'])
                ->where('is_class_teacher', true)
                ->get()
                ->map(function($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->user->name ?? 'N/A',
                        'email' => $teacher->user->email ?? 'N/A',
                        'phone' => $teacher->user->phone ?? 'N/A',
                        'class' => $teacher->assignedClass->name ?? 'N/A',
                        'class_id' => $teacher->assigned_class_id,
                        'section' => $teacher->assignedSection->name ?? 'N/A',
                        'section_id' => $teacher->assigned_section_id,
                        'student_count' => Student::where('current_class_id', $teacher->assigned_class_id)
                            ->where('current_section_id', $teacher->assigned_section_id)
                            ->count(),
                        'assigned_since' => $teacher->updated_at ? $teacher->updated_at->format('Y-m-d') : now()->format('Y-m-d')
                    ];
                });

            // Return with success wrapper
            return response()->json([
                'success' => true,
                'data' => $classTeachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load class teachers: ' . $e->getMessage()
            ], 500);
        }
    }
}