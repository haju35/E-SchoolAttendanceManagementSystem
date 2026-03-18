<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $family = $request->user()->family->load('user', 'students.user');
        
        return response()->json([
            'success' => true,
            'data' => $family
        ]);
    }
    
    public function update(Request $request)
    {
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
        $familyData = [];
        if ($request->has('occupation')) {
            $familyData['occupation'] = $request->occupation;
        }
        if ($request->has('emergency_contact')) {
            $familyData['emergency_contact'] = $request->emergency_contact;
        }
        
        if (!empty($familyData)) {
            $family->update($familyData);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $family->fresh(['user', 'students'])
        ]);
    }
}