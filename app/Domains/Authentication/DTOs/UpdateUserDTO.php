<?php

namespace App\Domains\Authentication\DTOs;

use App\Domains\Authentication\Requests\UpdateUserRequest;

/**
 * Class UpdateUserDTO
 *
 * Immutable Data Transfer Object for updating user details.
 */
class UpdateUserDTO
{
    public function __construct(
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $roleKey = null,
        public readonly ?string $contactNumber = null,
    ) {}

    public static function fromRequest(UpdateUserRequest $request): self
    {
        return new self(
            firstName:     $request->input('first_name'),
            lastName:      $request->input('last_name'),
            roleKey:       $request->input('role'),
            contactNumber: $request->input('contact_number'),
        );
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->firstName !== null) $data['first_name'] = $this->firstName;
        if ($this->lastName !== null) $data['last_name'] = $this->lastName;
        if ($this->contactNumber !== null) $data['contact_number'] = $this->contactNumber;
        return $data;
    }
}
