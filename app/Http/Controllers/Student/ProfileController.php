<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    // Show profile
    public function show(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student profile not found'
                ], 404);
            }
            
            $student = $user->student->load([
                'user', 
                'currentClass', 
                'currentSection',
                'family.user'
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $student
            ]);
            
        } catch (\Exception $e) {
            Log::error('Profile show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading profile'
            ], 500);
        }
    }

    // Update profile info
    public function update(Request $request)
    {
        try {
            $user = $request->user();
            
            $request->validate([
                'phone' => 'nullable|string|max:20',
            ]);
            
            if ($request->has('phone')) {
                $user->phone = $request->phone;
                $user->save();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
            
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating profile'
            ], 500);
        }
    }

    // Update password
    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
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
                'message' => 'Password updated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Password update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating password'
            ], 500);
        }
    }
    
    // Upload photo - FIXED VERSION
    public function uploadPhoto(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048'
            ]);

            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                
                // Generate unique filename
                $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                
                // Store the file
                $path = $file->storeAs('student_photos', $filename, 'public');
                
                // Delete old photo if exists
                if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                    Storage::disk('public')->delete($user->photo);
                }
                
                // Update user record
                $user->photo = $path;
                $user->save();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Photo uploaded successfully',
                    'photo_url' => Storage::url($path)
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No photo file provided'
            ], 400);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Photo upload error: ' . $e->getMessage());
            Log::error('Line: ' . $e->getLine());
            Log::error('File: ' . $e->getFile());
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading photo: ' . $e->getMessage()
            ], 500);
        }
    }
}