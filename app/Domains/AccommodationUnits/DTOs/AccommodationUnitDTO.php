<?php

namespace App\Domains\AccommodationUnits\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class AccommodationUnitDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?int $typeId,
        public readonly ?int $maxCapacity,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            name:        $request->input('name'),
            typeId:      $request->has('type_id') ? (int) $request->input('type_id') : null,
            maxCapacity: $request->has('max_capacity') ? (int) $request->input('max_capacity') : null,
        );
    }
    
    public function toArray(): array
    {
        return array_filter([
            'name'         => $this->name,
            'type_id'      => $this->typeId,
            'max_capacity' => $this->maxCapacity,
        ], fn($value) => $value !== null);
    }
}
