<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        try {
            $user = $request->user();
            $family = $user->family;
            
            if (!$family) {
                return response()->json([
                    'success' => false,
                    'message' => 'Family profile not found'
                ], 404);
            }
            
            // Format the response for frontend
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'address' => $user->address ?? '',
                'occupation' => $family->occupation ?? '',
                'emergency_contact' => $family->emergency_contact ?? '',
                'students' => $family->students()->with(['user', 'currentClass', 'currentSection'])->get()->map(function($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->user->name ?? 'N/A',
                        'roll_number' => $student->roll_number ?? 'N/A',
                        'class_name' => $student->currentClass->name ?? 'Not Assigned',
                        'section_name' => $student->currentSection->name ?? 'Not Assigned',
                        'admission_number' => $student->admission_number ?? 'N/A'
                    ];
                })
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function update(Request $request)
    {
        try {
            $user = $request->user();
            $family = $user->family;
            
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'occupation' => 'nullable|string|max:255',
                'emergency_contact' => 'nullable|string|max:20',
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
            
            // Update Family
            if ($family) {
                if ($request->has('occupation')) {
                    $family->occupation = $request->occupation;
                }
                if ($request->has('emergency_contact')) {
                    $family->emergency_contact = $request->emergency_contact;
                }
                $family->save();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'occupation' => $family ? $family->occupation : null,
                    'emergency_contact' => $family ? $family->emergency_contact : null
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();
            
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);
            
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
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
                'message' => 'Failed to change password: ' . $e->getMessage()
            ], 500);
        }
    }
}