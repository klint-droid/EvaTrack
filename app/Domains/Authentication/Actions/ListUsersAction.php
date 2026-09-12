<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class ListUsersAction
 *
 * Adheres to Single Responsibility Principle (SRP).
 * Handles user querying, role scoping based on caller authorization, and pagination.
 */
class ListUsersAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(User $authUser, ?string $search = null, ?string $role = null, int $perPage = 10): LengthAwarePaginator
    {
        // evac_admin cannot view super_admin
        $allowedRoles = $authUser->isEvacAdmin()
            ? ['evac_personnel', 'evac_admin']
            : ['evac_personnel', 'evac_admin', 'super_admin'];

        return $this->userRepository->paginateWithFilters(
            search: $search,
            role: $role,
            allowedRoles: $allowedRoles,
            perPage: $perPage
        );
    }
}
