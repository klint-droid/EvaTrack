<?php

namespace App\Domains\Households\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class MemberDTO
{
    public function __construct(
        public readonly string  $firstName,
        public readonly string  $lastName,
        public readonly string  $birthDate,
        public readonly ?string $middleName = null,
        public readonly ?int    $genderId = null,
        public readonly ?int    $relationshipId = null,
        public readonly ?int    $civilStatusId = null,
        public readonly ?array  $vulnerableGroupIds = null,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self(
            firstName:          $data['first_name'],
            lastName:           $data['last_name'],
            birthDate:          $data['birth_date'],
            middleName:         $data['middle_name'] ?? null,
            genderId:           $data['gender_id'] ?? null,
            relationshipId:     $data['relationship_id'] ?? null,
            civilStatusId:      $data['civil_status_id'] ?? null,
            vulnerableGroupIds: $data['vulnerable_group_ids'] ?? null,
        );
    }
}
