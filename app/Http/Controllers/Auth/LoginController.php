<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // Show login form (web)
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Handle web login
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials + ['is_active' => true], $remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Role-based redirect
            if ($user->role === 'admin') {
                return redirect()->intended(route('admin.dashboard'));
            } elseif ($user->role === 'teacher') {
                return redirect()->intended(route('teacher.dashboard'));
            } elseif ($user->role === 'student') {
                return redirect()->intended(route('student.dashboard'));
            } elseif ($user->role === 'family') {
                return redirect()->intended(route('family.dashboard'));
            }
            
            return redirect()->intended('/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    // Handle web logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // API Login


    public function apiLogin(LoginRequest $request)
    {
        try {

            $user = \App\Models\User::where('email', $request->email)->first();

            if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is deactivated. Please contact administrator.'
                ], 403);
            }

            $roleName = $user->getRoleNames()->first();
            $permissions = $user->getAllPermissions()->pluck('name');
            $isClassTeacher = false;
            if ($roleName === 'teacher' && $user->teacher) {
                $isClassTeacher = $user->teacher->is_class_teacher ?? false;
            }

            $tokenResult = $user->createToken('auth_token');

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'photo' => $user->photo,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'role' => $roleName,  // ← Role added here
                ],
                'access_token' => $tokenResult->accessToken,
                'role' => $roleName,  // Keep this for backward compatibility
                'permissions' => $permissions,
                'is_class_teacher' => $isClassTeacher,
                'redirect' => $roleName === 'teacher' && $isClassTeacher ? 'homeroom' : $roleName, 
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    // API Logout
    public function apiLogout(Request $request)
    {
        $request->user()->token()->revoke();
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    // API Refresh Token
    public function apiRefresh(Request $request)
    {
        $user = $request->user();
        $user->token()->revoke();
        $newToken = $user->createToken('auth_token')->accessToken;
        
        return response()->json([
            'success' => true,
            'access_token' => $newToken,
            'token_type' => 'Bearer',
        ]);
    }
    /*private function getUserScopes($user)
    {
        switch ($user->role) {
            case 'admin':
                return [
                    'admin:manage-users',
                    'admin:manage-students',
                    'admin:manage-teachers',
                    'admin:manage-classes',
                    'admin:view-all-attendance',
                    'admin:generate-reports',
                    'admin:configure-system',
                    'student:view-attendance',
                    'student:view-profile',
                    'teacher:mark-attendance',
                    'teacher:view-class',
                ];
                
            case 'teacher':
                return [
                    'teacher:mark-attendance',
                    'teacher:edit-attendance',
                    'teacher:view-class',
                    'teacher:view-reports',
                    'student:view-attendance',
                    'student:view-profile',
                ];
                
            case 'student':
                return [
                    'student:view-attendance',
                    'student:view-profile',
                    'student:update-profile',
                ];
                
            case 'family':
                return [
                    'family:view-children',
                    'family:view-child-attendance',
                    'family:receive-notifications',
                ];
                
            default:
                return ['basic:access'];
        }
    }*/
}