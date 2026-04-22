<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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

    // ADD THIS METHOD - Upload Photo
    public function uploadPhoto(Request $request)
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            
            $user = $request->user();
            
            // Delete old photo if exists
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            
            // Store new photo
            $file = $request->file('photo');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('teacher-photos', $filename, 'public');
            
            $user->photo = $path;
            $user->save();
            
            $photoUrl = asset('storage/' . $path);
            
            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'data' => ['photo' => $photoUrl]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ADD THIS METHOD - Change Password
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);
            
            $user = $request->user();
            
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            
            $user->password = Hash::make($request->new_password);
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}