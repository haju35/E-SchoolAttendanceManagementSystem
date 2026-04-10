<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // Show profile
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a student'
            ], 403);
        }

        $student = $user()->student->load([
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

    // Update profile info (name, phone, address)
    public function update(Request $request)
    {
        $user = $request->user();
        $student = $user->student;
        
        $request->validate([
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
        ]);
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $student->fresh(['user', 'currentClass', 'currentSection'])
        ]);
    }

    // Update password
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $user = $request->user();

        if ($request->hasFile('photo')) {
            if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
}
            $user->photo = $path;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'photo' => $user->photo
        ]);
    }
}