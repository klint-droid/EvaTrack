<?php

namespace App\Domains\Authentication\Controllers;

use App\Domains\Authentication\Actions\AssignCenterAction;
use App\Domains\Authentication\Actions\CreateUserAction;
use App\Domains\Authentication\Actions\DeleteUserAction;
use App\Domains\Authentication\Actions\ListUsersAction;
use App\Domains\Authentication\Actions\UpdatePasswordAction;
use App\Domains\Authentication\Actions\UpdateProfileAction;
use App\Domains\Authentication\Actions\UpdateUserAction;
use App\Domains\Authentication\DTOs\CreateUserDTO;
use App\Domains\Authentication\DTOs\UpdateProfileDTO;
use App\Domains\Authentication\DTOs\UpdateUserDTO;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use App\Domains\Authentication\Requests\AssignCenterRequest;
use App\Domains\Authentication\Requests\StoreUserRequest;
use App\Domains\Authentication\Requests\UpdatePasswordRequest;
use App\Domains\Authentication\Requests\UpdateProfileRequest;
use App\Domains\Authentication\Requests\UpdateUserRequest;
use App\Domains\Authentication\Resources\UserResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Class UserController
 *
 * Adheres to SOLID Principles:
 * - Single Responsibility (SRP): Thin HTTP orchestrator; business logic is delegated to Actions,
 *   validation to FormRequests, data transfer to DTOs, persistence to UserRepository, and serialization to UserResource.
 * - Dependency Inversion (DIP): Injects Actions and UserRepositoryInterface rather than directly referencing Eloquent models.
 * - Open-Closed (OCP): New capabilities or policies are added by creating/decorating Actions without bloating this controller.
 * - Liskov Substitution (LSP): All endpoints return standardized UserResource schemas.
 */
class UserController extends Controller
{
    /**
     * List users with optional search and role filtering.
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
    public function index(Request $request, ListUsersAction $action): JsonResponse
    {
        $users = $action->execute(
            authUser: $request->user(),
            search: $request->input('q'),
            role: $request->input('role')
        );

        $users->getCollection()->transform(function ($user) use ($request) {
            return (new UserResource($user))->toArray($request);
        });

        return response()->json($users);
    }

    /**
     * Create a new user.
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
    public function createUser(StoreUserRequest $request, CreateUserAction $action): JsonResponse
    {
        $dto = CreateUserDTO::fromRequest($request);
        $user = $action->execute($request->user(), $dto);

        return response()->json([
            'message' => 'User created successfully',
            'user'    => new UserResource($user),
        ], 201);
    }

    /**
     * Update an existing user.
     */
    #[OA\Put(
        path: '/users/{id}',
        summary: 'Update user',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
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
    public function updateUser(
        UpdateUserRequest $request,
        string $id,
        UpdateUserAction $action,
        UserRepositoryInterface $userRepository
    ): JsonResponse {
        $targetUser = $userRepository->findById($id);
        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $dto = UpdateUserDTO::fromRequest($request);
        $updatedUser = $action->execute($request->user(), $targetUser, $dto);

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => new UserResource($updatedUser),
        ]);
    }

    /**
     * Delete an existing user.
     */
    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Delete user',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function deleteUser(
        Request $request,
        string $id,
        DeleteUserAction $action,
        UserRepositoryInterface $userRepository
    ): JsonResponse {
        $targetUser = $userRepository->findById($id);
        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $action->execute($request->user(), $targetUser);

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Assign an evacuation center to a user.
     */
    #[OA\Post(
        path: '/users/{user}/assign-center',
        summary: 'Assign evacuation center to user',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(name: 'user', in: 'path', description: 'User ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'assigned_center_id', type: 'string', nullable: true)
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Assigned successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function assignCenter(
        AssignCenterRequest $request,
        string $user_id,
        AssignCenterAction $action,
        UserRepositoryInterface $userRepository
    ): JsonResponse {
        $targetUser = $userRepository->findById($user_id);
        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $action->execute(
            authUser: $request->user(),
            targetUser: $targetUser,
            assignedCenterId: $request->input('assigned_center_id')
        );

        return response()->json([
            'message' => $result['already_assigned']
                ? 'User already assigned to this center'
                : 'User assigned successfully',
            'data'    => new UserResource($result['user']),
        ]);
    }

    /**
     * Update current authenticated user's self profile.
     */
    public function updateProfile(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        $dto = UpdateProfileDTO::fromRequest($request);
        $updatedUser = $action->execute($request->user(), $dto);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => new UserResource($updatedUser),
        ]);
    }

    /**
     * Update current authenticated user's self password.
     */
    public function updatePassword(UpdatePasswordRequest $request, UpdatePasswordAction $action): JsonResponse
    {
        $action->execute(
            user: $request->user(),
            currentPassword: $request->input('current_password'),
            newPassword: $request->input('new_password')
        );

        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }
}