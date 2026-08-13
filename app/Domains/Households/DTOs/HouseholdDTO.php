<?php

namespace App\Domains\Households\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class HouseholdDTO
{
    public function __construct(
        public readonly string  $householdName,
        public readonly ?string $contactNumber = null,
        public readonly ?int    $addressId = null,
        public readonly ?string $barangay = null,
        public readonly ?string $street = null,
        public readonly ?string $purok = null,
        public readonly ?string $city = null,
        public readonly ?string $province = null,
        public readonly ?string $fullAddress = null,
        public readonly ?int    $memberCount = null,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self(
            householdName:  $data['household_name'] ?? '',
            contactNumber:  $data['contact_number'] ?? null,
            addressId:      $data['address_id'] ?? null,
            barangay:       $data['barangay'] ?? null,
            street:         $data['street'] ?? null,
            purok:          $data['purok'] ?? null,
            city:           $data['city'] ?? null,
            province:       $data['province'] ?? null,
            fullAddress:    $data['full_address'] ?? null,
            memberCount:    isset($data['member_count']) ? (int) $data['member_count'] : null,
        );
    }
}
