<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login again.'
            ], 401);
        }
        
        $user = Auth::user();
        
        // If no specific roles required, just allow access
        if (empty($roles)) {
            return $next($request);
        }
        
        // Check if user's role matches any of the allowed roles
        // Check using Spatie roles
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }
        
        // Role mismatch - return 403 with helpful message
        return response()->json([
            'success' => false,
            'message' => 'Access denied. This resource requires role: ' . implode(', ', $roles),
            'your_role' => $user->role,
            'required_roles' => $roles
        ], 403);
    }
}