<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    // Show reset password form (web)
    public function showResetForm($token)
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => request()->email
        ]);
    }

    // Handle password reset (web)
    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Your password has been reset successfully.');
        }

        return back()->withErrors(['email' => [__($status)]]);
    }

    // API Reset Password
    public function apiReset(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successful'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to reset password'
        ], 400);
    }
}