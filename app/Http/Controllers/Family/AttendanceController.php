<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $family = $request->user()->family;
        $children = $family->students()->pluck('id');

        $request->validate([
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date'
        ]);

        $query = Attendance::with(['student.user', 'class', 'section', 'subject', 'teacher.user'])
            ->whereIn('student_id', $children);

        if ($request->from_date) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $attendances = $query->orderBy('date', 'desc')->paginate(20);

        // Group by child for summary
        $summary = [];
        foreach ($children as $childId) {
            $childAttendances = $attendances->where('student_id', $childId);
            $total = $childAttendances->count();
            $present = $childAttendances->where('status', 'present')->count();
            
            $summary[$childId] = [
                'total' => $total,
                'present' => $present,
                'absent' => $childAttendances->where('status', 'absent')->count(),
                'late' => $childAttendances->where('status', 'late')->count(),
                'percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'attendances' => $attendances,
                'summary' => $summary
            ]
        ]);
    }
}