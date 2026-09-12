<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AssignCenterAction
 *
 * Adheres to Single Responsibility Principle (SRP).
 * Validates center assignment authorization and idempotently updates assigned_center_id.
 */
class AssignCenterAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * @return array{user: User, already_assigned: bool}
     */
    public function execute(User $authUser, User $targetUser, ?string $assignedCenterId): array
    {
        // Evac admin cannot assign a super admin
        if ($authUser->isEvacAdmin() && $targetUser->role?->role_key === 'super_admin') {
            throw new HttpException(403, 'You cannot assign a super admin');
        }

        // Idempotent check: if already assigned to this center, no-op
        if ($targetUser->assigned_center_id === $assignedCenterId) {
            return [
                'user'             => $targetUser,
                'already_assigned' => true,
            ];
        }

        $updated = $this->userRepository->update($targetUser, [
            'assigned_center_id' => $assignedCenterId,
        ]);

        return [
            'user'             => $updated,
            'already_assigned' => false,
        ];
    }
}
