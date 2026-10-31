<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Authentication\DTOs\CreateUserDTO;
use App\Domains\Authentication\Models\Role;
use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

/**
 * Class CreateUserAction
 *
 * Adheres to Single Responsibility Principle (SRP).
 * Encapsulates role resolution hierarchy, password hashing, and user persistence.
 */
class CreateUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(User $authUser, CreateUserDTO $dto): User
    {
        if (!$authUser->isSuperAdmin()) {
            abort(403, 'Only Super Admin can create accounts.');
        }

        // Super admins can assign any allowed role (defaults to evac_personnel)
        $roleKey = $dto->roleKey ?: 'evac_personnel';

        $role = Role::where('role_key', $roleKey)->firstOrFail();

        return $this->userRepository->create([
            'first_name'     => $dto->firstName,
            'last_name'      => $dto->lastName,
            'email'          => $dto->email ?: null,
            'password'       => Hash::make($dto->password),
            'role_id'        => $role->role_id,
            'contact_number' => $dto->contactNumber ?: null,
        ]);
    }
}
