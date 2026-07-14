<?php

namespace App\Domains\EvacuationCenters\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class EvacuationCenterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $capacity,
        public readonly ?string $osmAddress = null,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            name:       $request->input('name'),
            latitude:   (float) $request->input('latitude'),
            longitude:  (float) $request->input('longitude'),
            capacity:   (int) $request->input('capacity'),
            osmAddress: $request->input('osm_address'),
        );
    }
    
    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            'capacity'    => $this->capacity,
            'osm_address' => $this->osmAddress,
        ];
    }
}
