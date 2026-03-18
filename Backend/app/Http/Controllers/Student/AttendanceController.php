<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;
        
        $attendances = Attendance::with(['subject', 'teacher.user'])
            ->where('student_id', $student->id)
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
    
    public function summary(Request $request)
    {
        $student = $request->user()->student;
        
        $summary = [
            'daily' => Attendance::where('student_id', $student->id)
                ->selectRaw('date, status, count(*) as count')
                ->groupBy('date', 'status')
                ->orderBy('date', 'desc')
                ->take(30)
                ->get(),
            'monthly' => Attendance::where('student_id', $student->id)
                ->selectRaw('MONTH(date) as month, YEAR(date) as year, status, count(*) as count')
                ->groupBy('year', 'month', 'status')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get(),
            'subject_wise' => Attendance::where('student_id', $student->id)
                ->with('subject')
                ->selectRaw('subject_id, status, count(*) as count')
                ->groupBy('subject_id', 'status')
                ->get()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}