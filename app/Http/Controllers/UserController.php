<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(){
        return response()->json(User::paginate(10));
    }

    public function createUser(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'nullable|in:' . implode(',', [
                User::ROLE_USER,
                User::ROLE_ADMIN
            ])
        ]);

        $authUser = Auth::user();
        $role = User::ROLE_USER;

        if($authUser->isSuperAdmin() && $request->role){
            $role = $request->role;
        }

        if($authUser->isAdmin()){
            $role = User::ROLE_USER;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    public function updateUser(Request $request, $id){
        $user = User::findOrFail($id);
        $authUser = Auth::user();

        if ($user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Cannot update super admin'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id . ',user_id',
            'role' => 'sometimes|string'
        ]);

        if ($authUser->isAdmin()) {
            $user->update($request->only('name', 'email', 'role'));
        } else {
            $user->update($request->only('name', 'email'));
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    public function deleteUser($id){
        $user = User::findOrFail($id);
        $authUser = Auth::user();

        if ($authUser->user_id === $user->user_id) {
            return response()->json([
                'message' => 'You cannot delete yourself'
            ], 403);
        }

        if ($user->isSuperAdmin() && User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last super admin'
            ], 403);
        }

        if ($authUser->isAdmin() && !$user->isUser()) {
            return response()->json([
                'message' => 'Admin can only delete users'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}