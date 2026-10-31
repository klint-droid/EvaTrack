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
        public readonly ?string $email = null,
        public readonly ?string $roleKey = null,
        public readonly ?string $contactNumber = null,
        public readonly bool $hasEmail = false,
        public readonly bool $hasContactNumber = false,
    ) {}

    public static function fromRequest(UpdateUserRequest $request): self
    {
        return new self(
            firstName:        $request->input('first_name'),
            lastName:         $request->input('last_name'),
            email:            $request->input('email'),
            roleKey:          $request->input('role'),
            contactNumber:    $request->input('contact_number'),
            hasEmail:         $request->exists('email'),
            hasContactNumber: $request->exists('contact_number'),
        );
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->firstName !== null) $data['first_name'] = $this->firstName;
        if ($this->lastName !== null) $data['last_name'] = $this->lastName;
        if ($this->hasEmail) $data['email'] = $this->email ?: null;
        if ($this->hasContactNumber) $data['contact_number'] = $this->contactNumber ?: null;
        return $data;
    }
}
