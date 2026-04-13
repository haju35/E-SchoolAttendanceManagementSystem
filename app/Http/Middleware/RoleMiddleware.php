<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$scopes)
    {
        if (!$request->user() || !$request->user()->token()) {
            throw new AuthenticationException();
        }
        
        $tokenScopes = $request->user()->token()->scopes;
        
        foreach ($scopes as $scope) {
            if (!in_array($scope, $tokenScopes)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Forbidden',
                    'message' => 'You do not have permission to access this resource. Required scope: ' . $scope
                ], 403);
            }
        }
        
        return $next($request);
    }
}