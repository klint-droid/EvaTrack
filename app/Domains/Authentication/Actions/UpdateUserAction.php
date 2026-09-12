<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Authentication\DTOs\UpdateUserDTO;
use App\Domains\Authentication\Models\Role;
use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class UpdateUserAction
 *
 * Adheres to Single Responsibility Principle (SRP).
 * Validates role assignment authorization, prevents self-demotion, and updates user records.
 */
class UpdateUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(User $authUser, User $targetUser, UpdateUserDTO $dto): User
    {
        // Evac admin cannot modify super admin
        if ($authUser->isEvacAdmin() && $targetUser->role?->role_key === 'super_admin') {
            throw new HttpException(403, 'You cannot modify a super admin');
        }

        $data = $dto->toArray();

        // Super admin role change logic
        if ($authUser->isSuperAdmin() && $dto->roleKey !== null) {
            if ($authUser->user_id === $targetUser->user_id) {
                throw new HttpException(403, 'You cannot change your own role');
            }

            $role = Role::where('role_key', $dto->roleKey)->firstOrFail();
            $data['role_id'] = $role->role_id;
        }

        // Evac admin role change logic
        if ($authUser->isEvacAdmin() && $dto->roleKey !== null) {
            if ($dto->roleKey === 'super_admin') {
                throw new HttpException(403, 'Admin cannot assign super admin role');
            }

            $role = Role::where('role_key', $dto->roleKey)->firstOrFail();
            $data['role_id'] = $role->role_id;
        }

        return $this->userRepository->update($targetUser, $data);
    }
}
