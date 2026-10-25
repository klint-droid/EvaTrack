<?php

namespace App\Domains\EvacuationEvents\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class DisasterEventDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $typeId,
        public readonly ?int $severityId = null,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            name:       $request->input('name'),
            typeId:     (int) $request->input('type_id'),
            severityId: $request->filled('severity_id') ? (int) $request->input('severity_id') : null,
        );
    }
    
    public function toArray(): array
    {
        return [
            'name'              => $this->name,
            'type_id'           => $this->typeId,
            'severity_level_id' => $this->severityId,
            'started_at'        => now(),
        ];
    }
}
