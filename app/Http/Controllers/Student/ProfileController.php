<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $student = $request->user()->student->load([
            'user', 
            'currentClass', 
            'currentSection',
            'family.user'
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }
    
    public function update(Request $request)
    {
        $user = $request->user();
        $student = $user->student;
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);
        
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        if ($request->has('address')) {
            $user->address = $request->address;
        }
        
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $student->fresh(['user', 'currentClass', 'currentSection'])
        ]);
    }
}