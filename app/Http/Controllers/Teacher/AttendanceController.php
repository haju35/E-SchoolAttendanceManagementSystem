<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        $classes = $teacher->assignments->pluck('class')->unique();
        
        $students = [];
        $subjects = Subject::all();
        $date = $request->date ?? date('Y-m-d');
        
        if($request->class_room_id && $request->section_id) {
            $students = Student::where('current_class_id', $request->class_room_id)
                ->where('current_section_id', $request->section_id)
                ->get();
        }
        
        //return view('teacher.attendance.index', compact('classes', 'students', 'subjects', 'date'));
        return response()->json([
            'success' => true,
            'data' => [
                'classes' => $classes,
                'students' => $students,
                'subjects' => $subjects,
                'date' => $date
            ]
]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_room_id' => 'required',
            'section_id' => 'required',
            'date' => 'required|date',
            'attendance' => 'required|array'
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach($request->attendance as $studentId => $data) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'date' => $request->date,
                        'subject_id' => $data['subject_id']
                    ],
                    [
                        'class_room_id' => $request->class_id,
                        'section_id' => $request->section_id,
                        'teacher_id' => Auth::user()->teacher->id,
                        'status' => $data['status'],
                        'reason' => $data['reason'] ?? null
                    ]
                );
            }
            
            DB::commit();
            
            return redirect()->route('teacher.attendance.index')
                ->with('success', 'Attendance saved successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to save attendance: ' . $e->getMessage());
        }
    }
}