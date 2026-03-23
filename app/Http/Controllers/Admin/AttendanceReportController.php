<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceReportController extends Controller
{
    public function daily(Request $request)
    {
        $request->validate([
            'date' => 'sometimes|date',
            'class_id' => 'nullable|exists:class_rooms,id',
            'section_id' => 'nullable|exists:sections,id'
        ]);

        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $query = Attendance::with(['student.user', 'class', 'section', 'subject', 'teacher.user'])
            ->whereDate('date', $date);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        $attendances = $query->get();

        // Group by class/section for summary
        $summary = [
            'date' => $date->format('Y-m-d'),
            'total_records' => $attendances->count(),
            'by_status' => [
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count()
            ],
            'by_class' => $attendances->groupBy('class_id')->map(function($items) {
                return [
                    'class' => $items->first()->class->name ?? 'N/A',
                    'total' => $items->count(),
                    'present' => $items->where('status', 'present')->count()
                ];
            })->values(),
            'records' => $attendances
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function monthly(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
            'class_id' => 'nullable|exists:class_rooms,id'
        ]);

        $startDate = Carbon::create($request->year, $request->month, 1)->startOfMonth();
        $endDate = Carbon::create($request->year, $request->month, 1)->endOfMonth();

        $query = Attendance::with(['student.user', 'class', 'section'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        $attendances = $query->get();

        // Calculate daily summary
        $dailySummary = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayAttendances = $attendances->filter(function($a) use ($date) {
                return Carbon::parse($a->date)->isSameDay($date);
            });
            
            $dailySummary[] = [
                'date' => $date->format('Y-m-d'),
                'total' => $dayAttendances->count(),
                'present' => $dayAttendances->where('status', 'present')->count(),
                'absent' => $dayAttendances->where('status', 'absent')->count(),
                'late' => $dayAttendances->where('status', 'late')->count()
            ];
        }

        $summary = [
            'month' => $request->month,
            'year' => $request->year,
            'total_records' => $attendances->count(),
            'overall' => [
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count()
            ],
            'daily_breakdown' => $dailySummary,
            'class_breakdown' => $attendances->groupBy('class_id')->map(function($items) {
                return [
                    'class' => $items->first()->class->name ?? 'N/A',
                    'total' => $items->count(),
                    'present' => $items->where('status', 'present')->count(),
                    'absent' => $items->where('status', 'absent')->count(),
                    'late' => $items->where('status', 'late')->count()
                ];
            })->values()
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function byStudent($id)
    {
        $student = Student::with(['user', 'currentClass', 'currentSection'])->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        $attendances = Attendance::with(['subject', 'teacher.user'])
            ->where('student_id', $id)
            ->orderBy('date', 'desc')
            ->get();

        $totalDays = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();

        $summary = [
            'student' => $student,
            'statistics' => [
                'total_days' => $totalDays,
                'present' => $presentDays,
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'attendance_percentage' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0
            ],
            'monthly_breakdown' => $attendances->groupBy(function($a) {
                return Carbon::parse($a->date)->format('Y-m');
            })->map(function($items) {
                $total = $items->count();
                $present = $items->where('status', 'present')->count();
                return [
                    'month' => Carbon::parse($items->first()->date)->format('F Y'),
                    'total_days' => $total,
                    'present' => $present,
                    'absent' => $items->where('status', 'absent')->count(),
                    'late' => $items->where('status', 'late')->count(),
                    'percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0
                ];
            })->values(),
            'subject_wise' => $attendances->groupBy('subject_id')->map(function($items) {
                $total = $items->count();
                $present = $items->where('status', 'present')->count();
                return [
                    'subject' => $items->first()->subject->name ?? 'N/A',
                    'total_classes' => $total,
                    'present' => $present,
                    'absent' => $items->where('status', 'absent')->count(),
                    'late' => $items->where('status', 'late')->count(),
                    'percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0
                ];
            })->values(),
            'recent_attendances' => $attendances->take(20)
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function byClass($id)
    {
        $class = ClassRoom::with('sections')->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found'
            ], 404);
        }

        $request = request();
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $attendances = Attendance::with(['student.user', 'section', 'subject'])
            ->where('class_id', $id)
            ->whereDate('date', $date)
            ->get();

        $students = Student::with('user')
            ->where('current_class_id', $id)
            ->when($request->section_id, function($query, $sectionId) {
                $query->where('current_section_id', $sectionId);
            })
            ->get();

        // Create summary
        $summary = [
            'class' => $class,
            'date' => $date->format('Y-m-d'),
            'total_students' => $students->count(),
            'attendance_taken' => $attendances->count(),
            'by_section' => $attendances->groupBy('section_id')->map(function($items) use ($students) {
                $section = $items->first()->section ?? null;
                $sectionStudents = $students->where('current_section_id', $section?->id);
                return [
                    'section' => $section->name ?? 'N/A',
                    'total_students' => $sectionStudents->count(),
                    'marked' => $items->count(),
                    'present' => $items->where('status', 'present')->count(),
                    'absent' => $items->where('status', 'absent')->count(),
                    'late' => $items->where('status', 'late')->count()
                ];
            })->values(),
            'by_status' => [
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'not_marked' => $students->count() - $attendances->count()
            ],
            'details' => $students->map(function($student) use ($attendances) {
                $attendance = $attendances->firstWhere('student_id', $student->id);
                return [
                    'student_id' => $student->id,
                    'name' => $student->user->name,
                    'roll_number' => $student->roll_number,
                    'section' => $student->currentSection->name ?? 'N/A',
                    'status' => $attendance->status ?? 'not_marked',
                    'subject' => $attendance->subject->name ?? null,
                    'remarks' => $attendance->reason ?? null
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}