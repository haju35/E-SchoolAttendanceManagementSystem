<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
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

    public function store(Request $request)
    {
        $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'name' => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:1'
        ]);

        $section = Section::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully',
            'data' => $section->load('classRoom')
        ], 201);
    }

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

        $section->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => $section->load('classRoom')
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        // Validate new section (if students exist)
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