<?php

namespace App\Domains\Evacuations\DTOs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class AdmissionDTO
{
    public function __construct(
        public readonly string $householdId,
        public readonly int    $centerId,
        public readonly int    $userId,
        public readonly string $method = 'manual',
        public readonly ?int   $eventId = null,
        public readonly array  $memberIds = [],
        public readonly ?int   $memberCount = null,
    ) {}

    public static function fromRequest(Request|FormRequest $request, int $centerId, int $userId, string $method = 'manual'): self
    {
        return new self(
            householdId: $request->input('household_id'),
            centerId:    $centerId,
            userId:      $userId,
            method:      $method,
            eventId:     $request->input('event_id') ? (int) $request->input('event_id') : null,
            memberIds:   $request->input('member_ids', []),
            memberCount: $request->input('member_count') ? (int) $request->input('member_count') : null,
        );
    }
}
