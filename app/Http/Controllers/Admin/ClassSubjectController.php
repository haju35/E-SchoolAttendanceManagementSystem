<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Subject;
use Illuminate\Http\Request;

class ClassSubjectController extends Controller
{
    public function getSubjects($classId)
    {
        $class = ClassRoom::with('subjects')->findOrFail($classId);
        
        return response()->json([
            'success' => true,
            'data' => $class->subjects
        ]);
    }
    
    public function assignSubject(Request $request, $classId)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'is_compulsory' => 'boolean'
        ]);
        
        $class = ClassRoom::findOrFail($classId);
        
        // Check if already assigned
        if ($class->subjects()->where('subject_id', $request->subject_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Subject already assigned to this class'
            ], 422);
        }
        
        $class->subjects()->attach($request->subject_id, [
            'is_compulsory' => $request->is_compulsory ?? true
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subject assigned successfully'
        ]);
    }
    
    public function updateSubjectAssignment(Request $request, $classId, $subjectId)
    {
        $request->validate([
            'is_compulsory' => 'required|boolean'
        ]);
        
        $class = ClassRoom::findOrFail($classId);
        
        $class->subjects()->updateExistingPivot($subjectId, [
            'is_compulsory' => $request->is_compulsory
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully'
        ]);
    }
    
    public function removeSubject($classId, $subjectId)
    {
        $class = ClassRoom::findOrFail($classId);
        $class->subjects()->detach($subjectId);
        
        return response()->json([
            'success' => true,
            'message' => 'Subject removed successfully'
        ]);
    }
}