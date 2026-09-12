<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class DeleteUserAction
 *
 * Adheres to Single Responsibility Principle (SRP).
 * Enforces business constraints:
 * - Users cannot delete themselves.
 * - The system must always have at least one active super admin.
 * - Evacuation Admins may only delete personnel.
 */
class DeleteUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(User $authUser, User $targetUser): bool
    {
        // Cannot delete self
        if ($authUser->user_id === $targetUser->user_id) {
            throw new HttpException(403, 'You cannot delete yourself');
        }

        // Prevent deleting last super admin
        if ($targetUser->role?->role_key === 'super_admin' && $this->userRepository->countSuperAdmins() <= 1) {
            throw new HttpException(403, 'Cannot delete the last super admin');
        }

        // Evac admin can only delete personnel
        if ($authUser->isEvacAdmin() && $targetUser->role?->role_key !== 'evac_personnel') {
            throw new HttpException(403, 'Admin can only delete personnel');
        }

        return $this->userRepository->delete($targetUser);
    }
}
