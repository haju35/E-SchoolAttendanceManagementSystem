<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ResetPasswordController extends Controller
{
    /**
     * Show reset password form (web)
     */
    public function showResetForm($token)
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => request()->email
        ]);
    }

    /**
     * Reset password (web)
     */
    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                Log::info('Password reset successful for: ' . $user->email);
            }
        );
        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Your password has been reset successfully!');
        }

        return back()->withErrors([
            'email' => [__($status)]
        ]);
    }

    /**
     * API Reset Password
     */
    public function apiReset(ResetPasswordRequest $request)
    {
        try {
            // Validate token exists in database
            $resetRecord = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token.'
                ], 400);
            }

            // Check if token is expired (24 hours)
            $createdAt = Carbon::parse($resetRecord->created_at);
            if ($createdAt->diffInHours(now()) > 24) {
                DB::table('password_resets')->where('email', $request->email)->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired. Please request a new one.'
                ], 400);
            }

            // Find user
            $user = \App\Models\User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->remember_token = Str::random(60);
            $user->save();

            // Delete used reset token
            DB::table('password_resets')->where('email', $request->email)->delete();

            // Revoke all tokens for this user (force re-login)
            $user->tokens()->delete();

            Log::info('Password reset successful', ['email' => $request->email]);

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. You can now login with your new password.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password. Please try again.'
            ], 500);
        }
    }
}
