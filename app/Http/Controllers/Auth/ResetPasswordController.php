<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    public function apiReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find the password reset record
            $resetRecord = DB::table('password_resets')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token.'
                ], 400);
            }

            // Verify the token
            if (!Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reset token.'
                ], 400);
            }

            // Check if token is expired (60 minutes)
            $tokenCreatedAt = strtotime($resetRecord->created_at);
            $currentTime = time();
            $tokenAge = ($currentTime - $tokenCreatedAt) / 60; // in minutes

            if ($tokenAge > 60) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired. Please request a new one.'
                ], 400);
            }

            // Update user's password
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            // Delete the reset record
            DB::table('password_resets')->where('email', $request->email)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to reset password. Please try again.'
            ], 500);
        }
    }
}