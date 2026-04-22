<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function currentUser(Request $request)
    {
        return response()->json($request->user());
    }

    public function apiLogin(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt([
            'user_id' => $request->user_id,
            'password' => $request->password
        ])) {
            return response()->json([
                'message' => 'Invalid user ID or password'
            ], 401);
        }

        $user = Auth::user();

        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'role' => $user->role->role_key,
                'role_label' => $user->role->role_name,
                'assigned_center' => $user->assignedCenter ? [
                    'id' => $user->assignedCenter->evacuation_center_id,
                    'name' => $user->assignedCenter->name,
                ] : null,
            ]
        ]);
    }

    public function apiLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}