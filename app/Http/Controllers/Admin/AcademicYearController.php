<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $academicYears
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean'
        ]);

        // If this is set as current, unset other current
        if ($request->is_current) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
        }

        $academicYear = AcademicYear::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Academic year created successfully',
            'data' => $academicYear
        ], 201);
    }

    public function show($id)
    {
        $academicYear = AcademicYear::find($id);
        
        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Academic year not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $academicYear
        ]);
    }

    public function update(Request $request, $id)
    {
        $academicYear = AcademicYear::find($id);
        
        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Academic year not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_current' => 'boolean'
        ]);

        // If this is set as current, unset other current
        if ($request->has('is_current') && $request->is_current) {
            AcademicYear::where('is_current', true)
                ->where('id', '!=', $id)
                ->update(['is_current' => false]);
        }

        $academicYear->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Academic year updated successfully',
            'data' => $academicYear
        ]);
    }

    public function destroy($id)
    {
        $academicYear = AcademicYear::find($id);
        
        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Academic year not found'
            ], 404);
        }

        // If it's current, unset it first
        if ($academicYear->is_current) {
            $academicYear->is_current = false;
            $academicYear->save();
        }

        $academicYear->delete();

        return response()->json([
            'success' => true,
            'message' => 'Academic year deleted successfully'
        ]);
    }
}