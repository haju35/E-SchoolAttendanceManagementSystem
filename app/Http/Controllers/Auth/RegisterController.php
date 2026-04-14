<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    // Show registration form (web)
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    // Handle web registration
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student', // Default role for public registration
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect()->route('student.dashboard');
    }

    // API Registration
    public function apiRegister(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'student',
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    // API Update Profile
    public function apiUpdateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
        ]);
        
        $user->update($request->only('name', 'phone', 'address'));
        
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    // API Change Password
    // API Change Password - With Better Error Handling
    public function apiChangePassword(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
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
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Password change error: ' . $e->getMessage());
            \Log::error('Line: ' . $e->getLine());
            \Log::error('File: ' . $e->getFile());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}