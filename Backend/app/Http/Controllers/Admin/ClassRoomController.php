<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use Illuminate\Http\Request;

class ClassRoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $classes = ClassRoom::with(['sections', 'students'])
            ->when($request->search, function($query, $search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('numeric_value', 'LIKE', "%{$search}%");
            })
            ->orderBy('numeric_value')
            ->orderBy('name')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not needed for API - use store instead
        return response()->json([
            'success' => false,
            'message' => 'Use POST /api/admin/classes instead'
        ], 405);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'numeric_value' => 'required|integer|min:1|max:12'
        ]);

        // Check if class already exists
        $existingClass = ClassRoom::where('name', $request->name)
            ->orWhere('numeric_value', $request->numeric_value)
            ->first();

        if ($existingClass) {
            return response()->json([
                'success' => false,
                'message' => 'Class with this name or numeric value already exists'
            ], 422);
        }

        $class = ClassRoom::create([
            'name' => $request->name,
            'numeric_value' => $request->numeric_value
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully',
            'data' => $class
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ClassRoom $classRoom)
    {
        $classRoom->load([
            'sections',
            'students' => function($query) {
                $query->with('user', 'currentSection')->limit(10);
            },
            'subjects',
            'teacherAssignments' => function($query) {
                $query->with('teacher.user', 'section', 'subject');
            }
        ]);

        // Get statistics
        $totalStudents = $classRoom->students()->count();
        $totalSections = $classRoom->sections()->count();
        $totalSubjects = $classRoom->subjects()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'class' => $classRoom,
                'statistics' => [
                    'total_students' => $totalStudents,
                    'total_sections' => $totalSections,
                    'total_subjects' => $totalSubjects
                ]
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClassRoom $classRoom)
    {
        // Not needed for API - use update instead
        return response()->json([
            'success' => false,
            'message' => 'Use PUT /api/admin/classes/{id} instead'
        ], 405);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClassRoom $classRoom)
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'numeric_value' => 'sometimes|integer|min:1|max:12'
        ]);

        // Check if updating to existing values
        if ($request->has('name') || $request->has('numeric_value')) {
            $query = ClassRoom::where(function($q) use ($request) {
                if ($request->has('name')) {
                    $q->where('name', $request->name);
                }
                if ($request->has('numeric_value')) {
                    $q->orWhere('numeric_value', $request->numeric_value);
                }
            });

            if ($request->has('name')) {
                $query->where('name', $request->name);
            }
            if ($request->has('numeric_value')) {
                $query->where('numeric_value', $request->numeric_value);
            }

            $existingClass = $query->where('id', '!=', $classRoom->id)->first();

            if ($existingClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class with this name or numeric value already exists'
                ], 422);
            }
        }

        $classRoom->update($request->only(['name', 'numeric_value']));

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully',
            'data' => $classRoom->fresh(['sections', 'subjects'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClassRoom $classRoom)
    {
        // Check if class has students
        $studentCount = $classRoom->students()->count();
        if ($studentCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete class with ' . $studentCount . ' students. Move or delete students first.'
            ], 422);
        }

        // Check if class has sections
        $sectionCount = $classRoom->sections()->count();
        if ($sectionCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete class with ' . $sectionCount . ' sections. Delete sections first.'
            ], 422);
        }

        // Check if class has teacher assignments
        $assignmentCount = $classRoom->teacherAssignments()->count();
        if ($assignmentCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete class with teacher assignments. Remove assignments first.'
            ], 422);
        }

        $classRoom->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully'
        ]);
    }

    /**
     * Get sections for a specific class
     */
    public function sections(ClassRoom $classRoom)
    {
        $sections = $classRoom->sections()
            ->withCount('students')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }

    /**
     * Get students for a specific class
     */
    public function students(ClassRoom $classRoom, Request $request)
    {
        $students = $classRoom->students()
            ->with(['user', 'currentSection'])
            ->when($request->section_id, function($query, $sectionId) {
                $query->where('current_section_id', $sectionId);
            })
            ->when($request->search, function($query, $search) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                })->orWhere('roll_number', 'LIKE', "%{$search}%");
            })
            ->orderBy('roll_number')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }
}