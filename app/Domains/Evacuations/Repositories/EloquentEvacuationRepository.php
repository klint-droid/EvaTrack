<?php

namespace App\Domains\Evacuations\Repositories;

use App\Domains\Evacuations\DTOs\EvacuationFilterDTO;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Evacuations\Models\EvacuatedMember;
use App\Domains\Households\Models\HouseholdStatus;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Exceptions\NoCenterAssignedException;
use Illuminate\Database\Eloquent\Collection;

class EloquentEvacuationRepository implements EvacuationRepositoryInterface
{
    private function recordRelations(): array
    {
        return [
            'household.members',
            'household.members.gender',
            'household.members.relationship',
            'household.members.civilStatus',
            'household.members.vulnerableGroupDetails',
            'household.address',
            'evacuatedMembers.member',
            'unitAllocation.unit.type',
            'center',
            'event',
            'verifier',
        ];
    }

    public function getFilteredList(EvacuationFilterDTO $filters): Collection
    {
        $query = EvacuationRecord::with($this->recordRelations());

        if ($filters->householdStatusId !== null) {
            $query->where('household_status_id', $filters->householdStatusId);
        }

        if ($filters->eventId !== null) {
            $query->where('event_id', $filters->eventId);
        }

        if ($filters->centerId !== null) {
            $query->where('center_id', $filters->centerId);
        }

        return $query->latest('verified_at')->get();
    }

    public function findById(int $id, ?int $centerId = null): EvacuationRecord
    {
        $query = EvacuationRecord::with($this->recordRelations())
            ->where('evacuation_id', $id);

        if ($centerId !== null) {
            $query->where('center_id', $centerId);
        }

        return $query->firstOrFail();
    }

    public function createRecord(array $data): EvacuationRecord
    {
        return EvacuationRecord::create($data);
    }

    public function updateRecord(int $id, array $data): EvacuationRecord
    {
        $record = EvacuationRecord::findOrFail($id);
        $record->update($data);
        return $record->fresh($this->recordRelations());
    }

    public function deleteRecord(int $id): void
    {
        $record = EvacuationRecord::findOrFail($id);
        $record->delete();
    }

    public function createEvacuatedMembers(EvacuationRecord $record, array $memberIds): void
    {
        $data = array_map(function ($memberId) use ($record) {
            return [
                'evacuation_id' => $record->evacuation_id,
                'member_id'     => $memberId,
                'status'        => 'Inside Center',
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }, $memberIds);

        EvacuatedMember::insert($data);
    }

    public function createEvacuatedMembersWithCount(EvacuationRecord $record, int $count): void
    {
        // For unregistered households, we might not have member IDs. 
        // In the original service, it just inserted NULL for member_id or skipped.
        // Looking at the schema, if member_id is nullable, we insert placeholders.
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'evacuation_id' => $record->evacuation_id,
                'member_id'     => null,
                'status'        => 'Inside Center',
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        EvacuatedMember::insert($data);
    }

    public function updateEvacuatedMember(int $evacuationId, int $memberId, array $data): EvacuatedMember
    {
        $evacMember = EvacuatedMember::where('evacuation_id', $evacuationId)
            ->where('member_id', $memberId)
            ->firstOrFail();

        $evacMember->update($data);
        return $evacMember;
    }

    public function isHouseholdEvacuatedAtCenter(string $householdId, int $centerId): bool
    {
        return EvacuationRecord::where('household_id', $householdId)
            ->where('center_id', $centerId)
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->exists();
    }

    public function getEvacuatedCenterIdsForMembers(array $memberIds): array
    {
        return EvacuationRecord::where('household_status_id', HouseholdStatus::EVACUATED)
            ->whereHas('evacuatedMembers', function ($query) use ($memberIds) {
                $query->whereIn('member_id', $memberIds)
                      ->where('status', 'Inside Center');
            })
            ->pluck('center_id')
            ->unique()
            ->toArray();
    }

    public function resolveEventId(?int $eventId, int $centerId): int
    {
        if ($eventId) {
            return $eventId;
        }

        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

        if (!$center->current_event_id) {
            throw new NoCenterAssignedException(
                'This evacuation center has no active event assigned. Please contact your admin.'
            );
        }

        return $center->current_event_id;
    }
}
