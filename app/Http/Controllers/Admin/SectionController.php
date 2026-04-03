<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    // ✅ Get all sections
    public function index(Request $request)
    {
        $sections = Section::with('classRoom')
            ->when($request->class_room_id, function($query, $classId) {
                $query->where('class_room_id', $classId);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }

    // ✅ Create SINGLE section
    public function store(Request $request)
    {
        $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'name' => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:1'
        ]);

        // Prevent duplicate section name in same class
        $exists = Section::where('class_room_id', $request->class_room_id)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Section already exists in this class'
            ], 409);
        }

        $section = Section::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully',
            'data' => $section->load('classRoom')
        ], 201);
    }

    // ✅ Create MULTIPLE sections
    public function bulkStore(Request $request)
    {
        $request->validate([
            '*.class_room_id' => 'required|exists:class_rooms,id',
            '*.name' => 'required|string|max:50',
            '*.capacity' => 'nullable|integer|min:1'
        ]);

        $createdSections = [];

        foreach ($request->all() as $data) {

            // Prevent duplicates
            $exists = Section::where('class_room_id', $data['class_room_id'])
                ->where('name', $data['name'])
                ->exists();

            if (!$exists) {
                $createdSections[] = Section::create($data);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Sections created successfully',
            'data' => $createdSections
        ], 201);
    }

    // ✅ Show single section
    public function show($id)
    {
        $section = Section::with(['classRoom', 'students'])->find($id);
        
        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $section
        ]);
    }

    // ✅ Update section
    public function update(Request $request, $id)
    {
        $section = Section::find($id);
        
        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:50',
            'capacity' => 'nullable|integer|min:1'
        ]);

        // Prevent duplicate on update
        if ($request->name) {
            $exists = Section::where('class_room_id', $section->class_room_id)
                ->where('name', $request->name)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section name already exists in this class'
                ], 409);
            }
        }

        $section->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => $section->load('classRoom')
        ]);
    }

    // ✅ Delete section + move students
    public function destroy(Request $request, $id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        // If students exist → require move
        if ($section->students()->count() > 0) {
            $request->validate([
                'new_section_id' => 'required|exists:sections,id|different:' . $id
            ]);

            // Move students
            $section->students()->update([
                'current_section_id' => $request->new_section_id
            ]);
        }

        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section deleted and students moved successfully'
        ]);
    }
}