<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
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
        ])->get()->map(function($child) {
            // Calculate attendance stats
            $attendances = Attendance::where('student_id', $child->id)->get();
            $totalDays = $attendances->count();
            $presentDays = $attendances->where('status', 'present')->count();
            $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
            
            return [
                'id' => $child->id,
                'name' => $child->user->name ?? 'N/A',
                'class_name' => $child->currentClass->name ?? 'Not Assigned',
                'section' => $child->currentSection->name ?? 'Not Assigned',
                'roll_number' => $child->roll_number ?? 'N/A',
                'attendance_rate' => $attendancePercentage,
                'present_days' => $presentDays,
                'absent_days' => $attendances->where('status', 'absent')->count(),
                'late_days' => $attendances->where('status', 'late')->count(),
                'status' => $child->status ?? 'active'
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'children' => $children
            ]
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
        
        // Calculate attendance stats
        $attendances = Attendance::where('student_id', $child->id)->get();
        $totalDays = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();
        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
        
        $data = [
            'id' => $child->id,
            'name' => $child->user->name,
            'email' => $child->user->email,
            'class_name' => $child->currentClass->name ?? 'Not Assigned',
            'section' => $child->currentSection->name ?? 'Not Assigned',
            'roll_number' => $child->roll_number ?? 'N/A',
            'admission_number' => $child->admission_number ?? 'N/A',
            'attendance_rate' => $attendancePercentage,
            'present_days' => $presentDays,
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'late_days' => $attendances->where('status', 'late')->count()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $data
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
            ->when($request->month, function($query, $month) {
                $query->whereMonth('date', $month);
            })
            ->when($request->year, function($query, $year) {
                $query->whereYear('date', $year);
            })
            ->orderBy('date', 'desc')
            ->get()
            ->map(function($attendance) {
                return [
                    'id' => $attendance->id,
                    'date' => $attendance->date,
                    'status' => $attendance->status,
                    'subject_name' => $attendance->subject->name ?? 'N/A',
                    'teacher_name' => $attendance->teacher->user->name ?? 'N/A',
                    'check_in_time' => $attendance->check_in_time ?? '-',
                    'remarks' => $attendance->remarks ?? '-'
                ];
            });
        
        // Calculate stats for this child
        $allAttendances = $child->attendances()->get();
        $totalDays = $allAttendances->count();
        $presentDays = $allAttendances->where('status', 'present')->count();
        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
        
        return response()->json([
            'success' => true,
            'data' => [
                'records' => $attendances,
                'stats' => [
                    'attendance_rate' => $attendancePercentage,
                    'present_days' => $presentDays,
                    'absent_days' => $allAttendances->where('status', 'absent')->count(),
                    'late_days' => $allAttendances->where('status', 'late')->count(),
                    'total_days' => $totalDays
                ]
            ]
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