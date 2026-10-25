<?php

namespace App\Domains\ResourceRequests\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class ResourceRequestDTO
{
    public function __construct(
        public readonly ?string $evacuationCenterId,
        public readonly string $resourceType,
        public readonly int $quantity,
        public readonly ?string $description,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            evacuationCenterId: $request->has('evacuation_center_id') ? (string) $request->input('evacuation_center_id') : null,
            resourceType:       $request->input('resource_type'),
            quantity:           (int) $request->input('quantity'),
            description:        $request->input('description'),
        );
    }
    
    public function toArray(): array
    {
        return array_filter([
            'evacuation_center_id' => $this->evacuationCenterId,
            'resource_type'        => $this->resourceType,
            'quantity'             => $this->quantity,
            'description'          => $this->description,
        ], fn($value) => $value !== null);
    }
}
