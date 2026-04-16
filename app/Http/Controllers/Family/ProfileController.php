<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    // GET PROFILE
    public function getProfile()
    {
        $user = Auth::user();

        // Load children (students)
        $user->load('students.classRoom', 'students.section');

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'occupation' => $user->occupation,
                'emergency_contact' => $user->emergency_contact,
                'students' => $user->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'roll_number' => $student->roll_number,
                        'class_name' => $student->classRoom->name ?? '',
                        'section_name' => $student->section->name ?? ''
                    ];
                })
            ]
        ]);
    }

    // UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'phone' => $request->phone,
            'occupation' => $request->occupation,
            'emergency_contact' => $request->emergency_contact
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    }

    // CHANGE PASSWORD
    public function changePassword(Request $request)
    {
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}