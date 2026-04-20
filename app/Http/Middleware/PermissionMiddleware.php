<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login again.'
            ], 401);
        }
        
        $user = Auth::user();
        
        // Check if user has the required permission
        if (!$user->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have permission: ' . $permission,
                'your_permissions' => $user->getAllPermissions()->pluck('name'),
                'required_permission' => $permission
            ], 403);
        }
        
        return $next($request);
    }
}