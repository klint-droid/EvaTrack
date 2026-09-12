<?php

namespace App\Domains\Authentication\DTOs;

use App\Domains\Authentication\Requests\StoreUserRequest;

/**
 * Class CreateUserDTO
 *
 * Immutable Data Transfer Object for creating a user.
 * Decouples controller request parsing from domain business actions (SRP).
 */
class CreateUserDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $password,
        public readonly string $contactNumber,
        public readonly ?string $roleKey = null,
    ) {}

    public static function fromRequest(StoreUserRequest $request): self
    {
        return new self(
            firstName:     $request->input('first_name'),
            lastName:      $request->input('last_name'),
            password:      $request->input('password'),
            contactNumber: $request->input('contact_number'),
            roleKey:       $request->input('role'),
        );
    }

    public function toArray(): array
    {
        return [
            'first_name'     => $this->firstName,
            'last_name'      => $this->lastName,
            'password'       => $this->password,
            'contact_number' => $this->contactNumber,
            'role'           => $this->roleKey,
        ];
    }
}
