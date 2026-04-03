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

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        // Get all attendance for that month
        $attendances = Attendance::with('subject')
            ->where('student_id', $student->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        // ✅ Monthly stats
        $monthlyStats = [
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
        ];

        // ✅ Calendar data
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $calendarDays = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->format('Y-m-d');

            $record = $attendances->firstWhere('date', $date);

            $calendarDays[] = [
                'date' => $date,
                'day' => $day,
                'status' => $record->status ?? null
            ];
        }

        // ✅ Table records
        $records = $attendances->map(function ($a) {
            return [
                'id' => $a->id,
                'date' => $a->date,
                'day' => Carbon::parse($a->date)->format('l'),
                'status' => $a->status,
                'subject_name' => $a->subject->name ?? 'N/A',
                'check_in_time' => $a->marked_at ?? null,
                'remarks' => $a->reason
            ];
        });

        return response()->json([
            'success' => true,
            'monthly_stats' => $monthlyStats,
            'calendar_days' => $calendarDays,
            'records' => $records
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