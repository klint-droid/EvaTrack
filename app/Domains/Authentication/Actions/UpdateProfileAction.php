<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Authentication\DTOs\UpdateProfileDTO;
use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Class UpdateProfileAction
 *
 * Adheres to Single Responsibility Principle (SRP).
 * Handles file storage for profile avatar images and self-profile updates.
 */
class UpdateProfileAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(User $user, UpdateProfileDTO $dto): User
    {
        $data = [
            'first_name'     => $dto->firstName,
            'last_name'      => $dto->lastName,
            'contact_number' => $dto->contactNumber ?: null,
        ];

        if ($dto->hasEmail) {
            $data['email'] = $dto->email ?: null;
        }

        if ($dto->profilePhoto) {
            // Delete existing photo if present
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $path = $dto->profilePhoto->store('profile_photos', 'public');
            $data['profile_photo'] = $path;
        }

        return $this->userRepository->update($user, $data);
    }
}
