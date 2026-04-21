<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\Models\ClassRoom;
use App\Models\ClassTeacher;
use App\Models\Section;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClassTeacherController extends Controller
{
    /**
     * Get list of class teachers with pagination
     */
    public function index(Request $request)
    {
        try {
            $query = ClassTeacher::with(['teacher.user', 'classRoom', 'section']);
            
            // Search functionality
            if ($request->search) {
                $search = $request->search;
                $query->whereHas('teacher.user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('classRoom', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('section', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
            
            $classTeachers = $query->latest()->paginate(10);
            
            // Calculate statistics
            $totalClassTeachers = ClassTeacher::count();
            $totalClassesCovered = ClassTeacher::distinct('class_room_id')->count('class_room_id');
            
            // Get total students across all homeroom classes
            $totalStudents = 0;
            $allClassTeachers = ClassTeacher::with('classRoom')->get();
            foreach ($allClassTeachers as $ct) {
                if ($ct->classRoom) {
                    $totalStudents += $ct->classRoom->students()->count();
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $classTeachers,
                'statistics' => [
                    'total_class_teachers' => $totalClassTeachers,
                    'total_classes_covered' => $totalClassesCovered,
                    'total_students' => $totalStudents,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch class teachers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch class teachers: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get list for dropdown/select inputs
     */
    public function list(Request $request)
    {
        try {
            $classTeachers = ClassTeacher::with(['teacher.user', 'classRoom', 'section'])
                ->get()
                ->map(function($ct) {
                    return [
                        'id' => $ct->id,
                        'teacher_id' => $ct->teacher_id,
                        'teacher_name' => $ct->teacher->user->name ?? 'N/A',
                        'teacher_email' => $ct->teacher->user->email ?? 'N/A',
                        'class_id' => $ct->class_room_id,
                        'class_name' => $ct->classRoom->name ?? 'N/A',
                        'section_id' => $ct->section_id,
                        'section_name' => $ct->section->name ?? 'N/A',
                        'academic_year' => $ct->academic_year,
                        'students_count' => $ct->classRoom->students()->count() ?? 0,
                        'contact' => $ct->teacher->user->phone ?? 'N/A',
                        'created_at' => $ct->created_at,
                        // Also show if teacher has subject assignments
                        'subject_assignments_count' => TeacherAssignment::where('teacher_id', $ct->teacher_id)->count(),
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $classTeachers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch class teachers list: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch class teachers list: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Store a new class teacher assignment
     * A teacher can be both homeroom AND subject teacher
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'teacher_id' => 'required|exists:teachers,id',
                'class_id' => 'required|exists:class_rooms,id',
                'section_id' => 'required|exists:sections,id',
                'academic_year' => 'required|string',
            ]);
            
            $teacher = Teacher::findOrFail($request->teacher_id);
            
            // Check if already assigned as homeroom for the SAME class/section in this academic year
            $exists = ClassTeacher::where('teacher_id', $request->teacher_id)
                ->where('class_room_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('academic_year', $request->academic_year)
                ->exists();
                
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This teacher is already assigned as homeroom for this class/section for this academic year'
                ], 400);
            }
            
            // Check if teacher is already homeroom for a DIFFERENT class in same academic year
            $existingHomeroom = ClassTeacher::where('teacher_id', $request->teacher_id)
                ->where('academic_year', $request->academic_year)
                ->first();
                
            if ($existingHomeroom) {
                return response()->json([
                    'success' => false,
                    'message' => 'This teacher is already assigned as homeroom for another class/section in this academic year. A teacher can only be homeroom for ONE class per academic year.'
                ], 400);
            }
            
            // Check if class/section already has a homeroom teacher for this academic year
            $classHasHomeroom = ClassTeacher::where('class_room_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('academic_year', $request->academic_year)
                ->exists();
                
            if ($classHasHomeroom) {
                return response()->json([
                    'success' => false,
                    'message' => 'This class/section already has a homeroom teacher assigned for this academic year'
                ], 400);
            }
            
            // Create the homeroom assignment
            $classTeacher = ClassTeacher::create([
                'teacher_id' => $request->teacher_id,
                'class_room_id' => $request->class_id,
                'section_id' => $request->section_id,
                'academic_year' => $request->academic_year,
                'assigned_by' => auth()->id(),
            ]);
            
            // Also update the teacher's record for quick access
            $teacher->is_class_teacher = true;
            $teacher->assigned_class_id = $request->class_id;
            $teacher->assigned_section_id = $request->section_id;
            $teacher->save();
            
            // Note: This does NOT affect the teacher's subject assignments
            // The teacher can still teach subjects through teacher_assignments table
            
            return response()->json([
                'success' => true,
                'message' => 'Homeroom teacher assigned successfully. Teacher can still teach subjects.',
                'data' => $classTeacher->load(['teacher.user', 'classRoom', 'section'])
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to assign class teacher: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign class teacher: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Alternative store method
     */
    public function assign(Request $request)
    {
        return $this->store($request);
    }
    
    /**
     * Get single class teacher assignment
     */
    public function show($id)
    {
        try {
            $classTeacher = ClassTeacher::with(['teacher.user', 'classRoom', 'section'])->findOrFail($id);
            
            // Also get teacher's subject assignments
            $subjectAssignments = TeacherAssignment::where('teacher_id', $classTeacher->teacher_id)
                ->with(['subject', 'classRoom', 'section'])
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $classTeacher,
                'subject_assignments' => $subjectAssignments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Class teacher not found'
            ], 404);
        }
    }
    
    /**
     * Update class teacher assignment
     */
    public function update(Request $request, $id)
    {
        try {
            $classTeacher = ClassTeacher::findOrFail($id);
            
            $request->validate([
                'teacher_id' => 'sometimes|exists:teachers,id',
                'class_id' => 'sometimes|exists:class_rooms,id',
                'section_id' => 'sometimes|exists:sections,id',
                'academic_year' => 'sometimes|string',
            ]);
            
            $classTeacher->update($request->only(['teacher_id', 'class_room_id', 'section_id', 'academic_year']));
            
            // Update teacher record if needed
            if ($request->has('teacher_id')) {
                $newTeacher = Teacher::find($request->teacher_id);
                $newTeacher->is_class_teacher = true;
                $newTeacher->assigned_class_id = $classTeacher->class_room_id;
                $newTeacher->assigned_section_id = $classTeacher->section_id;
                $newTeacher->save();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Class teacher updated successfully',
                'data' => $classTeacher->load(['teacher.user', 'classRoom', 'section'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update class teacher: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete class teacher assignment
     */
    public function destroy($id)
    {
        try {
            $classTeacher = ClassTeacher::findOrFail($id);
            $teacherId = $classTeacher->teacher_id;
            
            $classTeacher->delete();
            
            // Update teacher record - remove homeroom status
            $teacher = Teacher::find($teacherId);
            if ($teacher) {
                $teacher->is_class_teacher = false;
                $teacher->assigned_class_id = null;
                $teacher->assigned_section_id = null;
                $teacher->save();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Homeroom assignment removed. Teacher remains as subject teacher if assigned.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove class teacher: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Alternative remove method
     */
    public function remove($id)
    {
        return $this->destroy($id);
    }
    
    /**
     * Get available teachers for dropdown (including those who already teach subjects)
     */
    public function getAvailableTeachers()
    {
        try {
            // Get all teachers, including those who already have subject assignments
            $teachers = Teacher::with('user')
                ->whereHas('user', function($q) {
                    $q->where('is_active', true);
                })
                ->get()
                ->map(function($teacher) {
                    $subjectCount = TeacherAssignment::where('teacher_id', $teacher->id)->count();
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->user->name,
                        'email' => $teacher->user->email,
                        'phone' => $teacher->user->phone,
                        'is_class_teacher' => $teacher->is_class_teacher,
                        'subject_assignments_count' => $subjectCount,
                        'can_be_homeroom' => !$teacher->is_class_teacher, // Can be homeroom if not already one
                    ];
                });
                
            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teachers'
            ], 500);
        }
    }
    
    /**
     * Get classes without assigned homeroom teachers
     */
    public function getAvailableClasses(Request $request)
    {
        try {
            $academicYear = $request->get('academic_year', date('Y'));
            
            $assignedClassIds = ClassTeacher::where('academic_year', $academicYear)
                ->pluck('class_room_id')
                ->toArray();
                
            $classes = ClassRoom::with('sections')
                ->whereNotIn('id', $assignedClassIds)
                ->get()
                ->map(function($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'sections' => $class->sections->map(function($section) {
                            return [
                                'id' => $section->id,
                                'name' => $section->name,
                                'student_count' => $section->students()->count(),
                            ];
                        })
                    ];
                });
                
            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch classes'
            ], 500);
        }
    }
    
    /**
     * Get teacher's complete profile (both homeroom and subject assignments)
     */
    public function getTeacherProfile($id)
    {
        try {
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
                    'subject_assignments' => $teacher->assignments,
                    'total_subjects' => $teacher->assignments->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }
    }
}