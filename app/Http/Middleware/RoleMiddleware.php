<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        //  Not logged in
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        // No roles passed
        if (empty($roles)) {
            return response()->json([
                'message' => 'No roles defined for this route'
            ], 500);
        }

        // get role key safely
        $userRole = $user->role?->role_key;

        // Role not allowed
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'message' => 'Forbidden',
                'user_role' => $userRole,
                'allowed_roles' => $roles
            ], 403);
        }

        return $next($request);
    }
}