<?php

namespace App\Domains\EvacuationEvents\DTOs;

use Illuminate\Http\Request;

class EventFilterDTO
{
    public function __construct(
        public readonly ?int $typeId = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            typeId:    $request->has('type_id') && $request->input('type_id') !== '' ? (int) $request->input('type_id') : null,
            startDate: $request->has('start_date') && $request->input('start_date') !== '' ? $request->input('start_date') : null,
            endDate:   $request->has('end_date') && $request->input('end_date') !== '' ? $request->input('end_date') : null,
        );
    }
}
