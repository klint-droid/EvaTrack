<?php

namespace App\Services;

use App\Models\EvacuationRecord;
use App\Models\EvacuationCenter;
use App\Models\Household;
use App\Models\EvacuatedMember;
use Illuminate\Support\Facades\DB;

class EvacuationService
{
    private function resolveEventId(?string $eventId, string $centerId): string
    {
        if ($eventId) {
            return $eventId;
        }

        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

        if (!$center->current_event_id) {
            throw new \Exception('This evacuation center has no active event assigned. Please contact your admin.');
        }

        return $center->current_event_id;
    }

    public function handleScan($householdId, $centerId, $userId, $method = 'qr', $eventId = null, array $memberIds = [])
    {
        return DB::connection('mysql_v2')->transaction(function () use (
            $householdId, $centerId, $userId, $method, $eventId, $memberIds
        ) {
            EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

            $this->ensureNotEvacuated($householdId, $centerId);

            $household = Household::with('members')
                ->where('household_id', $householdId)
                ->firstOrFail();

            if ($household->members->count() < 1) {
                throw new \Exception('This household has no registered members. Please add household members first.');
            }

            $admitMemberIds = !empty($memberIds)
                ? $memberIds
                : $household->members->pluck('member_id')->toArray();

            $this->ensureMembersNotEvacuatedElsewhere($admitMemberIds);

            $record = $this->createEvacuationRecord(
                $householdId,
                $centerId,
                $userId,
                count($admitMemberIds),
                $method,
                $eventId
            );

            $this->createEvacuatedMembersFromList($record, $admitMemberIds);

            return [
                'record'    => $record->fresh($this->recordRelations()),
                'household' => $household->fresh(['members', 'address']),
            ];
        });
    }

    public function handleManual($householdId, $centerId, $userId, $eventId = null, array $memberIds = [])
    {
        return DB::connection('mysql_v2')->transaction(function () use (
            $householdId, $centerId, $userId, $eventId, $memberIds
        ) {
            EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

            $this->ensureNotEvacuated($householdId, $centerId);

            $household = Household::with('members')
                ->where('household_id', $householdId)
                ->firstOrFail();

            $admitMemberIds = !empty($memberIds)
                ? $memberIds
                : $household->members->pluck('member_id')->toArray();

            $registeredMemberCount = count($admitMemberIds);

            if ($registeredMemberCount > 0) {
                $this->ensureMembersNotEvacuatedElsewhere($admitMemberIds);

                $record = $this->createEvacuationRecord(
                    $householdId, $centerId, $userId,
                    $registeredMemberCount, 'manual', $eventId
                );
                $this->createEvacuatedMembersFromList($record, $admitMemberIds);
            } else {
                $declaredCount = max(1, $household->members->count() ?: 1);

                $record = $this->createEvacuationRecord(
                    $householdId, $centerId, $userId,
                    $declaredCount, 'manual', $eventId
                );
            }

            return [
                'evacuation' => $record->fresh($this->recordRelations()),
                'household'  => $household->fresh(['members', 'address']),
            ];
        });
    }

    public function handleManualWithCount($householdId, $centerId, $userId, $count, $eventId = null)
    {
        return DB::connection('mysql_v2')->transaction(function () use (
            $householdId, $centerId, $userId, $count, $eventId
        ) {
            EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

            $this->ensureNotEvacuated($householdId, $centerId);

            $household = Household::where('household_id', $householdId)->firstOrFail();

            $declaredCount = max(1, (int) $count);

            $record = $this->createEvacuationRecord(
                $householdId, $centerId, $userId,
                $declaredCount, 'manual', $eventId
            );

            return [
                'evacuation' => $record->fresh($this->recordRelations()),
                'household'  => $household->fresh(['members', 'address']),
            ];
        });
    }

    private function createEvacuationRecord($householdId, $centerId, $userId, $count, $method, $eventId = null)
    {
        return EvacuationRecord::create([
            'household_id'       => $householdId,
            'center_id'          => $centerId,
            'event_id'           => $this->resolveEventId($eventId, $centerId),
            'household_status_id' => 2,
            'evacuated_count'    => $count,
            'method'             => $method,
            'verified_by'        => $userId,
            'verified_at'        => now(),
        ]);
    }

    private function createEvacuatedMembersFromHousehold(EvacuationRecord $record, Household $household): void
    {
        foreach ($household->members as $member) {
            EvacuatedMember::firstOrCreate(
                [
                    'evacuation_id' => $record->evacuation_id,
                    'member_id'     => $member->member_id,   // ✅ from HouseholdMember PK
                ],
                [
                    'verified_at' => now(),
                ]
            );
        }

        $evacuatedCount = EvacuatedMember::where('evacuation_id', $record->evacuation_id)->count();

        $record->update(['evacuated_count' => $evacuatedCount]);
    }

    private function ensureMembersNotEvacuatedElsewhere(array $memberIds): void
    {
        if (empty($memberIds)) {
            return;
        }

        $activeEvacuatedMembers = EvacuatedMember::whereIn('member_id', $memberIds)
            ->whereHas('evacuationRecord', function ($q) {
                $q->where('household_status_id', 2)
                  ->whereHas('event', function ($eq) {
                      $eq->whereNull('ended_at');
                  });
            })
            ->with(['member', 'evacuationRecord.center'])
            ->get();

        if ($activeEvacuatedMembers->isNotEmpty()) {
            $names = $activeEvacuatedMembers->map(function ($em) {
                $name = $em->member ? ($em->member->first_name . ' ' . $em->member->last_name) : $em->member_id;
                $centerName = $em->evacuationRecord && $em->evacuationRecord->center ? $em->evacuationRecord->center->center_name : 'another center';
                return "{$name} (at {$centerName})";
            })->join(', ');

            throw new \Exception("The following member(s) are already actively evacuated: {$names}. Please check them out first.");
        }
    }

    private function createEvacuatedMembersFromList(EvacuationRecord $record, array $memberIds): void
    {
        foreach ($memberIds as $memberId) {
            EvacuatedMember::firstOrCreate(
                [
                    'evacuation_id' => $record->evacuation_id,
                    'member_id'     => $memberId,
                ],
                [
                    'verified_at' => now(),
                ]
            );
        }

        $evacuatedCount = EvacuatedMember::where('evacuation_id', $record->evacuation_id)->count();

        $record->update(['evacuated_count' => $evacuatedCount]);
    }

    private function ensureNotEvacuated($householdId, $centerId): void
    {
        $exists = EvacuationRecord::where('household_id', $householdId)
            ->where('center_id', $centerId)
            ->where('household_status_id', 2)
            ->whereHas('event', function ($q) {
                $q->whereNull('ended_at');
            })
            ->exists();

        if ($exists) {
            throw new \Exception('Household already evacuated in this center.');
        }
    }


    private function recordRelations(): array
    {
        return [
            'household.members',
            'household.address',    
            'evacuatedMembers.member',
            'unitAllocation.unit.type', 
            'center',
            'event',
            'verifier',            
        ];
    }
}