<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(){
        $authUser = Auth::user();

        $query = User::query();

        if($authUser->isAdmin()){
            $query->where('role', '!=', User::ROLE_SUPER_ADMIN);
        }

        return response()->json($query->paginate(10));
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

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $authUser = Auth::user();

        // ❗ Admin cannot touch super admin
        if ($authUser->isAdmin() && $user->isSuperAdmin()) {
            return response()->json([
                'message' => 'You cannot modify a super admin'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id . ',user_id',
            'role' => 'sometimes|in:' . implode(',', [
                User::ROLE_USER,
                User::ROLE_ADMIN,
                User::ROLE_SUPER_ADMIN
            ])
        ]);

        $data = $request->only('name', 'email');

        // 🔥 SUPER ADMIN LOGIC
        if ($authUser->isSuperAdmin() && $request->has('role')) {

            // ❗ Prevent self role change
            if ($authUser->user_id === $user->user_id) {
                return response()->json([
                    'message' => 'You cannot change your own role'
                ], 403);
            }

            $data['role'] = $request->role;
        }

        // 🔥 ADMIN LOGIC
        if ($authUser->isAdmin() && $request->has('role')) {

            // ❗ Cannot assign super admin
            if ($request->role === User::ROLE_SUPER_ADMIN) {
                return response()->json([
                    'message' => 'Admin cannot assign super admin role'
                ], 403);
            }

            // ✅ Only allow user <-> admin switching
            if (
                in_array($user->role, [User::ROLE_USER, User::ROLE_ADMIN]) &&
                in_array($request->role, [User::ROLE_USER, User::ROLE_ADMIN])
            ) {
                $data['role'] = $request->role;
            }
        }

        $user->update($data);

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

    public function assignCenter(Request $request, $user_id)
    {
        $request->validate([
            'evacuation_center_id' => 'nullable|exists:evacuation_centers,evacuation_center_id'
        ]);

        $authUser = Auth::user();
        $user = User::findOrFail($user_id);

        // ❗ Admin cannot assign super admin
        if ($authUser->isAdmin() && $user->isSuperAdmin()) {
            return response()->json([
                'message' => 'You cannot assign a super admin'
            ], 403);
        }

        if ($user->assigned_evacuation_center_id === $request->evacuation_center_id) {
            return response()->json([
                'message' => 'User already assigned to this center'
            ], 200);
        }

        $user->update([
            'assigned_evacuation_center_id' => $request->evacuation_center_id
        ]);

        return response()->json([
            'message' => 'User assigned successfully'
        ]);
    }
}