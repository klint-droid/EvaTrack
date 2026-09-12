<?php

namespace App\Domains\Authentication\Repositories;

use App\Domains\Authentication\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class EloquentUserRepository
 *
 * Implements UserRepositoryInterface using Laravel Eloquent on the mysql_v2 database connection.
 * Encapsulates all direct database query operations for the User domain (SRP).
 */
class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginateWithFilters(?string $search, ?string $role, array $allowedRoles, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::with(['role', 'assignedCenter']);

        // Scope to caller's allowed roles
        $query->whereHas('role', function ($q) use ($allowedRoles) {
            $q->whereIn('role_key', $allowedRoles);
        });

        // Search query filter
        if ($search !== null && trim($search) !== '') {
            $q = trim($search);
            $query->where(function ($sub) use ($q) {
                $sub->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$q}%")
                    ->orWhere('user_id', 'like', "%{$q}%")
                    ->orWhere('contact_number', 'like', "%{$q}%");
            });
        }

        // Specific role filter
        if ($role !== null && trim($role) !== '') {
            $query->whereHas('role', function ($q) use ($role) {
                $q->where('role_key', $role);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?User
    {
        return User::with(['role', 'assignedCenter'])->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByContactNumber(string $contactNumber): ?User
    {
        return User::where('contact_number', $contactNumber)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): User
    {
        $user = User::create($data);
        return $user->load(['role', 'assignedCenter']);
    }

    /**
     * {@inheritDoc}
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->load(['role', 'assignedCenter']);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function countSuperAdmins(): int
    {
        return User::whereHas('role', function ($q) {
            $q->where('role_key', 'super_admin');
        })->count();
    }
}
