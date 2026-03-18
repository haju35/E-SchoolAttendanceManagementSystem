<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        abort(403, 'Registration is disabled. Please contact administrator.');
    }

    public function register()
    {
        abort(403, 'Registration is disabled. Please contact administrator.');
    }

    public function apiRegister()
    {
        return response()->json([
            'success' => false,
            'message' => 'Public registration is disabled. Please contact administrator to create your account.'
        ], 403);
    }

    public function apiProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status ?? 'active',
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ];

            if ($user->role === 'student' && $user->student) {
                $userData['student'] = $user->student;
                $userData['class'] = $user->student->class ?? null;
                $userData['section'] = $user->student->section ?? null;
            } elseif ($user->role === 'teacher' && $user->teacher) {
                $userData['teacher'] = $user->teacher;
            } elseif ($user->role === 'parent' && $user->parent) {
                $userData['parent'] = $user->parent;
                $userData['children'] = $user->parent->students ?? [];
            }

            return response()->json([
                'success' => true,
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            Log::error('Profile fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile'
            ], 500);
        }
    }

    public function apiUpdateProfile(UpdateProfileRequest $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('phone')) $updateData['phone'] = $request->phone;
            if ($request->has('address')) $updateData['address'] = $request->address;

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            if ($user->role === 'student' && $user->student) {
                $studentData = [];
                if ($request->has('parent_phone')) $studentData['parent_phone'] = $request->parent_phone;
                if ($request->has('emergency_contact')) $studentData['emergency_contact'] = $request->emergency_contact;

                if (!empty($studentData)) {
                    $user->student->update($studentData);
                }
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
                'message' => 'Failed to update profile'
            ], 500);
        }
    }

    public function apiChangePassword(ChangePasswordRequest $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            if ($request->user()->token()) {
                $currentTokenId = $request->user()->token()->id;
                $user->tokens()->where('id', '!=', $currentTokenId)->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Password change error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password'
            ], 500);
        }
    }
}