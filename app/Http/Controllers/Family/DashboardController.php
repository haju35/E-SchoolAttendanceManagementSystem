<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $family = $user->family;
            
            if (!$family) {
                return response()->json([
                    'success' => false,
                    'message' => 'Family profile not found'
                ], 404);
            }
            
            $children = $family->students()->with([
                'user', 
                'currentClass', 
                'currentSection',
            ])->get();
            
            $childrenData = [];
            $totalPresent = 0;
            $totalAttendanceSum = 0;
            
            foreach ($children as $child) {
                // Get attendance records
                $attendances = Attendance::where('student_id', $child->id)->get();
                
                $totalDays = $attendances->count();
                $presentDays = $attendances->where('status', 'present')->count();
                $absentDays = $attendances->where('status', 'absent')->count();
                $lateDays = $attendances->where('status', 'late')->count();
                
                $attendancePercentage = $totalDays > 0 
                    ? round(($presentDays / $totalDays) * 100, 2) 
                    : 0;
                
                $totalPresent += $presentDays;
                $totalAttendanceSum += $attendancePercentage;
                
                $childrenData[] = [
                    'id' => $child->id,
                    'name' => $child->user->name ?? 'N/A',
                    'class_name' => $child->currentClass->name ?? 'Not Assigned',
                    'section' => $child->currentSection->name ?? 'Not Assigned',
                    'roll_number' => $child->roll_number ?? 'N/A',
                    'attendance_rate' => $attendancePercentage,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                    'late_days' => $lateDays,
                    'total_days' => $totalDays
                ];
            }
            
            $childrenCount = count($childrenData);
            $avgAttendance = $childrenCount > 0 ? round($totalAttendanceSum / $childrenCount, 2) : 0;
            
            // Get recent alerts (absences from last 7 days)
            $recentAlerts = [];
            $recentAbsences = Attendance::whereIn('student_id', $children->pluck('id'))
                ->where('status', 'absent')
                ->with('student.user')
                ->whereDate('date', '>=', now()->subDays(7))
                ->latest()
                ->take(5)
                ->get();
            
            foreach ($recentAbsences as $absence) {
                $recentAlerts[] = [
                    'id' => $absence->id,
                    'type' => 'absent',
                    'message' => $absence->student->user->name . ' was absent on ' . date('F j, Y', strtotime($absence->date)),
                    'created_at' => $absence->created_at
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'family_name' => $user->name,
                    'children' => $childrenData,
                    'recent_alerts' => $recentAlerts,
                    'pending_leaves' => 0,
                    'stats' => [
                        'total_children' => $childrenCount,
                        'avg_attendance' => $avgAttendance,
                        'total_present' => $totalPresent
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Family Dashboard Error: ' . $e->getMessage());
            \Log::error('Line: ' . $e->getLine());
            \Log::error('File: ' . $e->getFile());
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getChildAttendance($childId, Request $request)
    {
        try {
            $user = $request->user();
            $family = $user->family;
            
            // Verify child belongs to this family
            $child = $family->students()->where('students.id', $childId)->first();
            
            if (!$child) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child not found or not linked to your account'
                ], 404);
            }
            
            $attendances = Attendance::where('student_id', $childId)
                ->with('subject')
                ->orderBy('date', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $attendances
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}