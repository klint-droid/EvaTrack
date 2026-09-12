<?php

namespace App\Domains\Authentication\DTOs;

use App\Domains\Authentication\Requests\UpdateProfileRequest;
use Illuminate\Http\UploadedFile;

/**
 * Class UpdateProfileDTO
 *
 * Immutable Data Transfer Object for self-profile updates.
 */
class UpdateProfileDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $contactNumber,
        public readonly ?UploadedFile $profilePhoto = null,
    ) {}

    public static function fromRequest(UpdateProfileRequest $request): self
    {
        return new self(
            firstName:     $request->input('first_name'),
            lastName:      $request->input('last_name'),
            contactNumber: $request->input('contact_number'),
            profilePhoto:  $request->file('profile_photo'),
        );
    }
}
