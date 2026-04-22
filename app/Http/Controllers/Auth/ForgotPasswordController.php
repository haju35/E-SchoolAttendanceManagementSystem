<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    public function apiSendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        
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
    
    private function sendResetEmail($user, $resetUrl)
    {
        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'resetUrl' => $resetUrl,
            'app_name' => config('app.name')
        ];
        
        Mail::send('emails.password-reset', $data, function ($message) use ($user) {
            $message->to($user->email, $user->name)
                    ->subject('Reset Your Password - ' . config('app.name'));
        });
    }
}