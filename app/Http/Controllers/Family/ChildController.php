<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function index(Request $request)
    {
        $family = $request->user()->family;
        
        $children = $family->students()->with([
            'user', 
            'currentClass', 
            'currentSection'
        ])->get();
        
        return response()->json([
            'success' => true,
            'data' => $children
        ]);
    }
    
    public function show(Request $request, $id)
    {
        $family = $request->user()->family;
        
        $child = Student::where('family_id', $family->id)
            ->with(['user', 'currentClass', 'currentSection'])
            ->find($id);
        
        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'Child not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $child
        ]);
    }
    
    public function attendance(Request $request, $id)
    {
        $family = $request->user()->family;
        
        $child = Student::where('family_id', $family->id)->find($id);
        
        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'Child not found'
            ], 404);
        }
        
        $attendances = $child->attendances()
            ->with(['subject', 'teacher.user'])
            ->when($request->from_date, function($query, $fromDate) {
                $query->whereDate('date', '>=', $fromDate);
            })
            ->when($request->to_date, function($query, $toDate) {
                $query->whereDate('date', '<=', $toDate);
            })
            ->orderBy('date', 'desc')
            ->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }
    
    public function summary(Request $request, $id)
    {
        $family = $request->user()->family;
        
        $child = Student::where('family_id', $family->id)->find($id);
        
        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'Child not found'
            ], 404);
        }
        
        $totalDays = $child->attendances()->count();
        $presentDays = $child->attendances()->where('status', 'present')->count();
        
        $summary = [
            'total_days' => $totalDays,
            'present' => $presentDays,
            'absent' => $child->attendances()->where('status', 'absent')->count(),
            'late' => $child->attendances()->where('status', 'late')->count(),
            'percentage' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0,
            'monthly_breakdown' => $child->attendances()
                ->selectRaw('MONTH(date) as month, YEAR(date) as year, status, count(*) as count')
                ->groupBy('year', 'month', 'status')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}