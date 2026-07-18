<?php

namespace App\Domains\Evacuations\DTOs;

use Illuminate\Http\Request;

class EvacuationFilterDTO
{
    public function __construct(
        public readonly ?int $householdStatusId = null,
        public readonly ?string $centerId = null,
        public readonly ?string $eventId = null,
    ) {}

    public static function fromRequest(Request $request, ?string $assignedCenterId = null): self
    {
        // If the user has an assigned center, enforce it; otherwise take from query
        $centerId = $assignedCenterId ?: ($request->query('center_id') ? (string) $request->query('center_id') : null);

        return new self(
            householdStatusId: $request->query('household_status_id') ? (int) $request->query('household_status_id') : null,
            centerId:          $centerId,
            eventId:           $request->query('event_id') ? (string) $request->query('event_id') : null,
        );
    }
}
