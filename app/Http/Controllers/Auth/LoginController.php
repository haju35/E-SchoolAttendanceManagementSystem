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
            } elseif ($user->role === 'parent') {
                return redirect()->intended(route('parent.dashboard'));
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
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->accessToken;

            return response()->json([
                'success' => true,
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'role' => $user->role
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
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
            'token_type' => 'Bearer'
        ]);
    }
}