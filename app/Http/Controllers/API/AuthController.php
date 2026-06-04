<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Get(
        path: '/user',
        summary: 'Get current logged in user',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function currentUser(Request $request)
    {
        return response()->json(
            $request->user()->load(['role', 'assignedCenter'])
        );
    }

    #[OA\Post(
        path: '/login',
        summary: 'Log in user',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['user_id', 'password'],
            properties: [
                new OA\Property(property: 'user_id', type: 'string', example: 'admin'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Login successful')]
    #[OA\Response(response: 401, description: 'Invalid user ID or password')]
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

    #[OA\Post(
        path: '/logout',
        summary: 'Log out user',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Logged out successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function apiLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}