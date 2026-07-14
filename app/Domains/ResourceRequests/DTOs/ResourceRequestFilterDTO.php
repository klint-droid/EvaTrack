<?php

namespace App\Domains\ResourceRequests\DTOs;

use Illuminate\Http\Request;

class ResourceRequestFilterDTO
{
    public function __construct(
        public readonly ?int $centerId,
        public readonly ?string $status,
        public readonly ?int $urgencyId,
        public readonly ?string $q,
        public readonly int $limit,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            centerId:  $request->has('center_id') ? (int) $request->query('center_id') : null,
            status:    $request->query('status'),
            urgencyId: $request->has('urgency_id') ? (int) $request->query('urgency_id') : null,
            q:         $request->query('q'),
            limit:     (int) $request->query('limit', 0),
        );
    }
}
