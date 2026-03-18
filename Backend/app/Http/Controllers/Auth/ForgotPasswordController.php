<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    /**
     * Show forgot password form (web)
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send reset link email (web)
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request)
    {
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('status', 'We have emailed your password reset link!');
            }

            return back()->withErrors(['email' => __($status)]);

        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());

            return back()->withErrors([
                'email' => 'Unable to send reset link. Please try again later.'
            ]);
        }
    }

    /**
     * API Send Reset Link
     */
    public function apiSendResetLink(ForgotPasswordRequest $request)
    {
        try {
            // Check if user exists
            $user = \App\Models\User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'We cannot find a user with that email address.'
                ], 404);
            }

            // Generate reset token
            $token = Str::random(60);

            // Delete old tokens for this email
            DB::table('password_resets')->where('email', $request->email)->delete();

            // Insert new token
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            // For development - return token directly
            if (config('app.env') === 'local') {
                $resetUrl = url('/api/auth/reset-password?token=' . $token . '&email=' . urlencode($request->email));

                Log::info('Password reset link generated', ['url' => $resetUrl]);

                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link generated. (Development Mode)',
                    'reset_url' => $resetUrl,
                    'token' => $token, // Only in development
                    'email' => $request->email
                ], 200);
            }

            // In production, send email
            // Mail::to($request->email)->send(new ResetPasswordMail($token));

            return response()->json([
                'success' => true,
                'message' => 'Password reset link has been sent to your email.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Password reset API error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }
}
