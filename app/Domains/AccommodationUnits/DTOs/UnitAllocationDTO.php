<?php

namespace App\Domains\AccommodationUnits\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class UnitAllocationDTO
{
    public function __construct(
        public readonly int $evacuationId,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            evacuationId: (int) $request->input('evacuation_id'),
        );
    }
    
    public function toArray(): array
    {
        return [
            'evacuation_id' => $this->evacuationId,
        ];
    }
}
