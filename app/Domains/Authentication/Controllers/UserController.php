<?php

namespace App\Domains\Authentication\Controllers;

use Illuminate\Http\Request;
use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;

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
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }

    /**
     * LIST USERS
     */
    #[OA\Get(
        path: '/users',
        summary: 'List users',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Search term for name/contact', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'role', in: 'query', description: 'Filter by role', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function index(Request $request)
    {
        $authUser = Auth::user();

        $query = User::with('role');

        // Since the database is shared, limit queries to only the app's relevant roles
        $allowedRoles = ['evac_personnel', 'evac_admin', 'super_admin'];

        if ($authUser->isEvacAdmin()) {
            // evac_admin cannot see super_admin
            $allowedRoles = ['evac_personnel', 'evac_admin'];
        }

        $query->whereHas('role', function ($q) use ($allowedRoles) {
            $q->whereIn('role_key', $allowedRoles);
        });

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function($sub) use ($q) {
                $sub->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$q}%")
                    ->orWhere('user_id', 'like', "%{$q}%")
                    ->orWhere('contact_number', 'like', "%{$q}%");
            });
        }

        if ($request->filled('role')) {
            $role = $request->input('role');
            $query->whereHas('role', function($q) use ($role) {
                $q->where('role_key', $role);
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
    #[OA\Post(
        path: '/users',
        summary: 'Create user',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['first_name', 'last_name', 'password', 'contact_number'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'role', type: 'string', enum: ['evac_personnel', 'evac_admin', 'super_admin']),
                new OA\Property(property: 'contact_number', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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
    #[OA\Put(
        path: '/users/{id}',
        summary: 'Update user',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'role', type: 'string', enum: ['evac_personnel', 'evac_admin', 'super_admin']),
                new OA\Property(property: 'contact_number', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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
    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Delete user',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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
    #[OA\Post(
        path: '/users/{user}/assign-center',
        summary: 'Assign evacuation center to user',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(name: 'user', in: 'path', description: 'User ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'assigned_center_id', type: 'integer', nullable: true)
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Assigned successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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

    /**
     * UPDATE SELF PROFILE
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_number' => 'required|string|unique:users,contact_number,' . $user->user_id . ',user_id',
            'profile_photo' => 'nullable|image|max:5120',
        ]);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'contact_number' => $request->contact_number,
        ];

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo);
            }
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $data['profile_photo'] = $path;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $this->formatUser($user)
        ]);
    }

    /**
     * UPDATE SELF PASSWORD
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The provided current password does not match your record.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }
}