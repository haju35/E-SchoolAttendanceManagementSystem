<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    // List subjects
    public function index(Request $request)
    {
        $limit = $request->limit ?? 20;
        $subjects = Subject::with('classes')->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    // Create subject
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code'
        ]);

        $subject = Subject::create($request->only(['name','code']));

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => $subject
        ], 201);
    }

    // Show subject details
    public function show($id)
    {
        $subject = Subject::with(['classes', 'teacherAssignments.teacher.user'])->find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subject
        ]);
    }

    // Update subject
    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:subjects,code,' . $id
        ]);

        $subject->update($request->only(['name','code']));

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => $subject
        ]);
    }

    // Delete subject
    public function destroy($id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully'
        ]);
    }
}