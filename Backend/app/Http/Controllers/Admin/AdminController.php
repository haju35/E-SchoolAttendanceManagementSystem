<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Family;
use App\Models\ClassRoom;
use App\Models\Attendance;


class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_students' => Student::count(),
            'total_teachers' => Teacher::count(),
            'total_families' => Family::count(),
            'total_classes' => ClassRoom::count(),
            'today_attendance' => Attendance::whereDate('created_at', today())->count(),
            'recent_students' => Student::with('user')->latest()->take(5)->get(),
            'recent_teachers' => Teacher::with('user')->latest()->take(5)->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}