<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    // Show forgot password form (web)
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    // Send reset link (web)
    public function sendResetLinkEmail(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    // API Send Reset Link
    public function apiSendResetLink(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send reset link'
        ], 400);
    }
}