<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = Auth::user()->teacher;
        $classes = $teacher->assignments->pluck('class')->unique();
        
        $data = [
            'my_classes' => $classes,
            'total_students' => $classes->sum(function($class) {
                return $class->students->count();
            }),
            'today_attendance' => Attendance::where('teacher_id', $teacher->id)
                ->whereDate('date', today())
                ->count(),
            'subjects_count' => $teacher->assignments->count()
        ];
        
        return view('teacher.dashboard', compact('data'));
    }
}