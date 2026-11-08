<?php

namespace App\Domains\Evacuations\Repositories;

use App\Domains\Evacuations\DTOs\EvacuationFilterDTO;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Evacuations\Models\EvacuatedMember;
use App\Domains\Households\Models\HouseholdStatus;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\EvacuationEvents\Models\DisasterEvent;
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
        HouseholdStatus::ensureSeeded();

        $query = EvacuationRecord::with($this->recordRelations());

        if ($filters->householdStatusId !== null) {
            $query->where('household_status_id', $filters->householdStatusId);
        }

        if ($filters->eventId !== null) {
            $query->where(function ($q) use ($filters) {
                $q->where('event_id', $filters->eventId)
                  ->orWhereNull('event_id');
            });
        }

        if ($filters->centerId !== null) {
            $query->where('center_id', $filters->centerId);
        }

        return $query->latest('verified_at')->get();
    }

    public function findById(int $id, ?string $centerId = null): EvacuationRecord
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
        HouseholdStatus::ensureSeeded();
        $record = EvacuationRecord::create($data);
        if (!empty($record->center_id)) {
            $this->syncCenterOccupancy((string) $record->center_id);
        }
        return $record;
    }

    public function updateRecord(int $id, array $data): EvacuationRecord
    {
        HouseholdStatus::ensureSeeded();
        $record = EvacuationRecord::findOrFail($id);
        $record->update($data);
        if (!empty($record->center_id)) {
            $this->syncCenterOccupancy((string) $record->center_id);
        }
        return $record->fresh($this->recordRelations());
    }

    public function deleteRecord(int $id): void
    {
        $record = EvacuationRecord::findOrFail($id);
        $centerId = $record->center_id ? (string) $record->center_id : null;
        $record->delete();
        if ($centerId) {
            $this->syncCenterOccupancy($centerId);
        }
    }

    public function syncCenterOccupancy(string $centerId): void
    {
        $totalOccupancy = (int) EvacuationRecord::where('center_id', $centerId)
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->where(function ($q) {
                $q->whereNull('event_id')
                  ->orWhereHas('event', function ($eq) {
                      $eq->whereNull('ended_at');
                  });
            })
            ->sum('evacuated_count');

        \Illuminate\Support\Facades\DB::connection('mysql_v2')
            ->table('evacuation_centers')
            ->where('evacuation_center_id', $centerId)
            ->update(['current_occupancy' => $totalOccupancy]);
    }

    public function createEvacuatedMembers(EvacuationRecord $record, array $memberIds): void
    {
        $existingMemberIds = EvacuatedMember::where('evacuation_id', $record->evacuation_id)
            ->whereIn('member_id', $memberIds)
            ->pluck('member_id')
            ->toArray();

        $newMemberIds = array_values(array_diff($memberIds, $existingMemberIds));
        if (empty($newMemberIds)) {
            return;
        }

        $data = array_map(function ($memberId) use ($record) {
            return [
                'evacuation_id' => $record->evacuation_id,
                'member_id'     => $memberId,
                'verified_at'   => now(),
            ];
        }, $newMemberIds);

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
                'verified_at'   => now(),
            ];
        }
        EvacuatedMember::insert($data);
    }

    public function updateEvacuatedMember(int $evacuationId, string $memberId, array $data): EvacuatedMember
    {
        $evacMember = EvacuatedMember::where('evacuation_id', $evacuationId)
            ->where('member_id', $memberId)
            ->firstOrFail();

        $evacMember->update($data);
        return $evacMember;
    }

    public function isHouseholdEvacuatedAtCenter(string $householdId, string $centerId): bool
    {
        return EvacuationRecord::where('household_id', $householdId)
            ->where('center_id', $centerId)
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->where(function ($q) {
                $q->whereNull('event_id')
                  ->orWhereHas('event', function ($eq) {
                      $eq->whereNull('ended_at');
                  });
            })
            ->exists();
    }

    public function getEvacuatedCenterIdsForMembers(array $memberIds): array
    {
        // Auto-close any stale evacuation records belonging to ended disaster events
        $staleRecordIds = EvacuationRecord::where('household_status_id', HouseholdStatus::EVACUATED)
            ->whereNotNull('event_id')
            ->where(function ($q) {
                $q->whereDoesntHave('event')
                  ->orWhereHas('event', function ($eq) {
                      $eq->whereNotNull('ended_at');
                  });
            })
            ->pluck('evacuation_id')
            ->toArray();

        if (!empty($staleRecordIds)) {
            HouseholdStatus::ensureSeeded();
            EvacuationRecord::whereIn('evacuation_id', $staleRecordIds)->update([
                'household_status_id' => HouseholdStatus::CHECKED_OUT,
            ]);
            \App\Domains\AccommodationUnits\Models\UnitAllocation::whereIn('evacuation_id', $staleRecordIds)->delete();
        }

        return EvacuationRecord::where('household_status_id', HouseholdStatus::EVACUATED)
            ->where(function ($q) {
                $q->whereNull('event_id')
                  ->orWhereHas('event', function ($eq) {
                      $eq->whereNull('ended_at');
                  });
            })
            ->whereHas('evacuatedMembers', function ($query) use ($memberIds) {
                $query->whereIn('member_id', $memberIds);
            })
            ->pluck('center_id')
            ->unique()
            ->values()
            ->toArray();
    }

    public function resolveEventId(?string $eventId, string $centerId): ?string
    {
        if ($eventId) {
            $validExplicit = DisasterEvent::where('event_id', $eventId)
                ->whereNull('ended_at')
                ->first();
            if ($validExplicit) {
                return (string) $validExplicit->event_id;
            }
        }

        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->first();

        if ($center && $center->current_event_id) {
            $activeCenterEvent = DisasterEvent::where('event_id', $center->current_event_id)
                ->whereNull('ended_at')
                ->first();
            if ($activeCenterEvent) {
                return (string) $activeCenterEvent->event_id;
            }
            $center->update(['current_event_id' => null]);
        }

        $activeEvent = DisasterEvent::whereNull('ended_at')->latest('started_at')->first();
        if ($activeEvent) {
            if ($center && $center->current_event_id !== $activeEvent->event_id) {
                $center->update(['current_event_id' => $activeEvent->event_id]);
            }
            return (string) $activeEvent->event_id;
        }

        return null;
    }
}
