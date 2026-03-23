<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;


use App\Models\TeacherAssignment;
use App\Models\Teacher;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherAssignmentController extends Controller
{
    /**
     * Display a listing of teacher assignments.
     */
    public function index(Request $request)
    {
        $assignments = TeacherAssignment::with(['teacher.user', 'subject', 'class', 'section'])
            ->when($request->teacher_id, function($query, $teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->when($request->class_id, function($query, $classId) {
                $query->where('class_id', $classId);
            })
            ->when($request->section_id, function($query, $sectionId) {
                $query->where('section_id', $sectionId);
            })
            ->when($request->subject_id, function($query, $subjectId) {
                $query->where('subject_id', $subjectId);
            })
            ->when($request->academic_year, function($query, $year) {
                $query->where('academic_year', $year);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not needed for API
        return response()->json([
            'success' => false,
            'message' => 'Use POST endpoint instead'
        ], 405);
    }

    /**
     * Store a newly created teacher assignment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:class_rooms,id',
            'section_id' => 'required|exists:sections,id',
            'academic_year' => 'required|string|max:20'
        ]);

        // Check if assignment already exists
        $exists = TeacherAssignment::where('teacher_id', $request->teacher_id)
            ->where('subject_id', $request->subject_id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('academic_year', $request->academic_year)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This teacher is already assigned to this class/subject/section for the academic year'
            ], 422);
        }

        // Check if another teacher is already assigned to this subject/class/section
        $otherAssignment = TeacherAssignment::where('subject_id', $request->subject_id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('academic_year', $request->academic_year)
            ->first();

        if ($otherAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'This subject is already assigned to another teacher for this class/section'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $assignment = TeacherAssignment::create([
                'teacher_id' => $request->teacher_id,
                'subject_id' => $request->subject_id,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'academic_year' => $request->academic_year
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Teacher assigned successfully',
                'data' => $assignment->load(['teacher.user', 'subject', 'class', 'section'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified assignment.
     */
    public function show(TeacherAssignment $teacherAssignment)
    {
        $teacherAssignment->load(['teacher.user', 'subject', 'class', 'section']);

        return response()->json([
            'success' => true,
            'data' => $teacherAssignment
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TeacherAssignment $teacherAssignment)
    {
        // Not needed for API
        return response()->json([
            'success' => false,
            'message' => 'Use PUT endpoint instead'
        ], 405);
    }

    /**
     * Update the specified assignment.
     */
    public function update(Request $request, TeacherAssignment $teacherAssignment)
    {
        $request->validate([
            'teacher_id' => 'sometimes|exists:teachers,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'class_id' => 'sometimes|exists:class_rooms,id',
            'section_id' => 'sometimes|exists:sections,id',
            'academic_year' => 'sometimes|string|max:20'
        ]);

        // Check for duplicates if changing key fields
        if ($request->hasAny(['teacher_id', 'subject_id', 'class_id', 'section_id', 'academic_year'])) {
            $exists = TeacherAssignment::where('teacher_id', $request->teacher_id ?? $teacherAssignment->teacher_id)
                ->where('subject_id', $request->subject_id ?? $teacherAssignment->subject_id)
                ->where('class_id', $request->class_id ?? $teacherAssignment->class_id)
                ->where('section_id', $request->section_id ?? $teacherAssignment->section_id)
                ->where('academic_year', $request->academic_year ?? $teacherAssignment->academic_year)
                ->where('id', '!=', $teacherAssignment->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This assignment already exists'
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            $teacherAssignment->update($request->only([
                'teacher_id', 'subject_id', 'class_id', 'section_id', 'academic_year'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'data' => $teacherAssignment->fresh(['teacher.user', 'subject', 'class', 'section'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified assignment.
     */
    public function destroy(TeacherAssignment $teacherAssignment)
    {
        // Check if there are any attendance records using this assignment
        $attendanceCount = $teacherAssignment->teacher->attendances()
            ->where('subject_id', $teacherAssignment->subject_id)
            ->where('class_id', $teacherAssignment->class_id)
            ->where('section_id', $teacherAssignment->section_id)
            ->count();

        if ($attendanceCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete assignment with existing attendance records'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $teacherAssignment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teachers by class and section.
     */
    public function getByClassAndSection($classId, Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'academic_year' => 'sometimes|string'
        ]);

        $academicYear = $request->academic_year ?? date('Y');

        $assignments = TeacherAssignment::with(['teacher.user', 'subject'])
            ->where('class_id', $classId)
            ->where('section_id', $request->section_id)
            ->where('academic_year', $academicYear)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Get subjects by teacher.
     */
    public function getByTeacher($teacherId, Request $request)
    {
        $request->validate([
            'academic_year' => 'sometimes|string'
        ]);

        $academicYear = $request->academic_year ?? date('Y');

        $assignments = TeacherAssignment::with(['subject', 'class', 'section'])
            ->where('teacher_id', $teacherId)
            ->where('academic_year', $academicYear)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }
}