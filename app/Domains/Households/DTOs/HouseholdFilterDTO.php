<?php

namespace App\Domains\Households\DTOs;

use Illuminate\Http\Request;

class HouseholdFilterDTO
{
    public function __construct(
        public readonly int     $page = 1,
        public readonly string  $search = '',
        public readonly string  $status = '',
        public readonly ?int    $centerId = null,
        public readonly ?int    $eventId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            page:     (int) $request->query('page', 1),
            search:   $request->query('q', ''),
            status:   $request->query('status', ''),
            centerId: $request->query('center_id') ? (int) $request->query('center_id') : null,
            eventId:  $request->query('event_id') ? (int) $request->query('event_id') : null,
        );
    }
}
