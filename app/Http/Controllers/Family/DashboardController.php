<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $family = $request->user()->family;
        
        $children = $family->students()->with([
            'user', 
            'currentClass', 
            'currentSection',
            'attendances' => function($q) {
                $q->latest()->take(10);
            }
        ])->get();
        
        // Calculate attendance for each child
        foreach ($children as $child) {
            $total = $child->attendances->count();
            $present = $child->attendances->where('status', 'present')->count();
            $child->attendance_percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
        }
        
        $data = [
            'family' => $family->load('user'),
            'children' => $children,
            'notifications' => $request->user()->receivedNotifications()
                ->latest()
                ->take(5)
                ->get()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}