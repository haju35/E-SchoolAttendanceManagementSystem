<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function index(Request $request)
    {
        $terms = Term::with('academicYear')
            ->when($request->academic_year_id, function($query, $academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $terms
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'academic_year_id' => 'required|exists:academic_years,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean'
        ]);

        // If this is set as current, unset other current terms in same academic year
        if ($request->is_current) {
            Term::where('academic_year_id', $request->academic_year_id)
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        $term = Term::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Term created successfully',
            'data' => $term->load('academicYear')
        ], 201);
    }

    public function show($id)
    {
        $term = Term::with('academicYear')->find($id);
        
        if (!$term) {
            return response()->json([
                'success' => false,
                'message' => 'Term not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $term
        ]);
    }

    public function update(Request $request, $id)
    {
        $term = Term::find($id);
        
        if (!$term) {
            return response()->json([
                'success' => false,
                'message' => 'Term not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_current' => 'boolean'
        ]);

        // If this is set as current, unset other current terms in same academic year
        if ($request->has('is_current') && $request->is_current) {
            Term::where('academic_year_id', $term->academic_year_id)
                ->where('is_current', true)
                ->where('id', '!=', $id)
                ->update(['is_current' => false]);
        }

        $term->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Term updated successfully',
            'data' => $term->load('academicYear')
        ]);
    }

    public function destroy($id)
    {
        $term = Term::find($id);
        
        if (!$term) {
            return response()->json([
                'success' => false,
                'message' => 'Term not found'
            ], 404);
        }

        $term->delete();

        return response()->json([
            'success' => true,
            'message' => 'Term deleted successfully'
        ]);
    }
}