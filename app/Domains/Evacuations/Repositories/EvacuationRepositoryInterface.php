<?php

namespace App\Domains\Evacuations\Repositories;

use App\Domains\Evacuations\DTOs\EvacuationFilterDTO;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Evacuations\Models\EvacuatedMember;
use Illuminate\Database\Eloquent\Collection;

interface EvacuationRepositoryInterface
{
    public function getFilteredList(EvacuationFilterDTO $filters): Collection;
    public function findById(int $id, ?string $centerId = null): EvacuationRecord;
    public function createRecord(array $data): EvacuationRecord;
    public function updateRecord(int $id, array $data): EvacuationRecord;
    public function deleteRecord(int $id): void;
    
    public function createEvacuatedMembers(EvacuationRecord $record, array $memberIds): void;
    public function createEvacuatedMembersWithCount(EvacuationRecord $record, int $count): void;
    public function updateEvacuatedMember(int $evacuationId, string $memberId, array $data): EvacuatedMember;
    
    public function isHouseholdEvacuatedAtCenter(string $householdId, string $centerId): bool;
    public function getEvacuatedCenterIdsForMembers(array $memberIds): array;
    
    public function resolveEventId(?string $eventId, string $centerId): string;
}
