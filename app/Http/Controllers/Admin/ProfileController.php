<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $teacher = $request->user()->teacher->load([
            'user',
            'assignments.subject',
            'assignments.classRoom',
            'assignments.section'
        ]);

        return response()->json([
            'success' => true,
            'data' => $teacher
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $teacher = $user->teacher;

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
        ]);

        // Update User
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

        // Update Teacher
        if ($request->has('qualification')) {
            $teacher->qualification = $request->qualification;
            $teacher->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $teacher->fresh(['user', 'assignments'])
        ]);
    }
}