<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Class UpdatePasswordAction
 *
 * Adheres to Single Responsibility Principle (SRP).
 * Validates existing password hash and persists updated password.
 */
class UpdatePasswordAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password does not match your record.'],
            ]);
        }

        $this->userRepository->update($user, [
            'password' => Hash::make($newPassword),
        ]);

        return true;
    }
}
