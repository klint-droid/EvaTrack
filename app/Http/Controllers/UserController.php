<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * FORMAT USER (reusable)
     */
    private function formatUser($user)
    {
        return [
            'user_id' => $user->user_id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'role' => $user->role?->role_key,
            'role_label' => $user->role?->role_name,
            'assigned_center_id' => $user->assigned_center_id,
            'contact_number' => $user->contact_number,
        ];
    }

    /**
     * LIST USERS
     */
    public function index()
    {
        $authUser = Auth::user();

        $query = User::with('role');

        //  evac_admin cannot see super_admin
        if ($authUser->isEvacAdmin()) {
            $query->whereHas('role', function ($q) {
                $q->where('role_key', '!=', 'super_admin');
            });
        }

        $users = $query->paginate(10);

        $users->getCollection()->transform(function ($user) {
            return $this->formatUser($user);
        });

        return response()->json($users);
    }

    /**
     * CREATE USER
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'role' => 'nullable|in:evac_personnel,evac_admin,super_admin',
            'contact_number' => 'required|string|unique:users,contact_number'
        ]);

        $authUser = Auth::user();

        // default role
        $roleKey = 'evac_personnel';

        if ($authUser->isSuperAdmin() && $request->role) {
            $roleKey = $request->role;
        }

        if ($authUser->isEvacAdmin()) {
            $roleKey = 'evac_personnel';
        }

        $role = Role::where('role_key', $roleKey)->firstOrFail();

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => Hash::make($request->password),
            'role_id' => $role->role_id,
            'contact_number' => $request->contact_number,
        ]);

        $user->load('role');

        return response()->json([
            'message' => 'User created successfully',
            'user' => $this->formatUser($user),
        ], 201);
    }

    /**
     * UPDATE USER
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::with('role')->findOrFail($id);
        $authUser = Auth::user();

        // evac admin cannot modify super admin
        if ($authUser->isEvacAdmin() && $user->role?->role_key === 'super_admin') {
            return response()->json([
                'message' => 'You cannot modify a super admin'
            ], 403);
        }

        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:evac_personnel,evac_admin,super_admin',
            'contact_number' => 'sometimes|string|unique:users,contact_number'
        ]);

        $data = [];

        if ($request->has('first_name')) {
            $data['first_name'] = $request->first_name;
        }

        if ($request->has('last_name')) {
            $data['last_name'] = $request->last_name;
        }

        //  SUPER ADMIN ROLE CHANGE
        if ($authUser->isSuperAdmin() && $request->has('role')) {

            if ($authUser->user_id === $user->user_id) {
                return response()->json([
                    'message' => 'You cannot change your own role'
                ], 403);
            }

            $role = Role::where('role_key', $request->role)->firstOrFail();
            $data['role_id'] = $role->role_id;
        }

        //  EVAC ADMIN ROLE CHANGE
        if ($authUser->isEvacAdmin() && $request->has('role')) {

            if ($request->role === 'super_admin') {
                return response()->json([
                    'message' => 'Admin cannot assign super admin role'
                ], 403);
            }

            $role = Role::where('role_key', $request->role)->firstOrFail();
            $data['role_id'] = $role->role_id;
        }

        $user->update($data);
        $user->load('role');

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * DELETE USER
     */
    public function deleteUser($id)
    {
        $user = User::with('role')->findOrFail($id);
        $authUser = Auth::user();

        // ❌ cannot delete yourself
        if ($authUser->user_id === $user->user_id) {
            return response()->json([
                'message' => 'You cannot delete yourself'
            ], 403);
        }

        // ❌ prevent deleting last super admin
        $superAdminCount = User::whereHas('role', function ($q) {
            $q->where('role_key', 'super_admin');
        })->count();

        if ($user->role?->role_key === 'super_admin' && $superAdminCount <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last super admin'
            ], 403);
        }

        // ❌ evac admin restriction
        if ($authUser->isEvacAdmin() && $user->role?->role_key !== 'evac_personnel') {
            return response()->json([
                'message' => 'Admin can only delete personnel'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * ASSIGN CENTER
     */
    public function assignCenter(Request $request, $user_id)
    {
        $request->validate([
            'assigned_center_id' => 'nullable|exists:evacuation_centers,evacuation_center_id'
        ]);

        $authUser = Auth::user();
        $user = User::with('role')->findOrFail($user_id);

        // ❌ evac admin cannot assign super admin
        if ($authUser->isEvacAdmin() && $user->role?->role_key === 'super_admin') {
            return response()->json([
                'message' => 'You cannot assign a super admin'
            ], 403);
        }

        // ✅ no change
        if ($user->assigned_center_id === $request->assigned_center_id) {
            return response()->json([
                'message' => 'User already assigned to this center',
                'data' => $this->formatUser($user)
            ]);
        }

        $user->update([
            'assigned_center_id' => $request->assigned_center_id
        ]);

        $user->load('role');

        return response()->json([
            'message' => 'User assigned successfully',
            'data' => $this->formatUser($user)
        ]);
    }
}