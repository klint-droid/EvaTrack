<?php

namespace App\Domains\ResourceRequests\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class ResourceRequestDTO
{
    public function __construct(
        public readonly ?int $evacuationCenterId,
        public readonly string $resourceType,
        public readonly int $quantity,
        public readonly ?string $description,
        public readonly int $urgencyId,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            evacuationCenterId: $request->has('evacuation_center_id') ? (int) $request->input('evacuation_center_id') : null,
            resourceType:       $request->input('resource_type'),
            quantity:           (int) $request->input('quantity'),
            description:        $request->input('description'),
            urgencyId:          (int) $request->input('urgency_id'),
        );
    }
    
    public function toArray(): array
    {
        return array_filter([
            'evacuation_center_id' => $this->evacuationCenterId,
            'resource_type'        => $this->resourceType,
            'quantity'             => $this->quantity,
            'description'          => $this->description,
            'urgency_id'           => $this->urgencyId,
        ], fn($value) => $value !== null);
    }
}
