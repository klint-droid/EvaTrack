<?php

namespace App\Domains\Authentication\Repositories;

use App\Domains\Authentication\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface UserRepositoryInterface
 *
 * Adheres to Dependency Inversion Principle (DIP).
 * High-level business actions depend on this abstraction rather than concrete Eloquent models.
 */
interface UserRepositoryInterface
{
    /**
     * Retrieve paginated users filtered by search term, specific role, and allowed roles.
     *
     * @param string|null $search Search query matching name, user_id, or contact number.
     * @param string|null $role Specific role_key filter.
     * @param array $allowedRoles Whitelist of role keys caller is permitted to see.
     * @param int $perPage Number of records per page.
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(?string $search, ?string $role, array $allowedRoles, int $perPage = 10): LengthAwarePaginator;

    /**
     * Find a user by primary key (user_id).
     *
     * @param string $id
     * @return User|null
     */
    public function findById(string $id): ?User;

    /**
     * Find a user by contact number.
     *
     * @param string $contactNumber
     * @return User|null
     */
    public function findByContactNumber(string $contactNumber): ?User;

    /**
     * Create and persist a new user record.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User;

    /**
     * Update an existing user record.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function update(User $user, array $data): User;

    /**
     * Delete an existing user record.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool;

    /**
     * Count total active super admins in the system.
     *
     * @return int
     */
    public function countSuperAdmins(): int;
}
