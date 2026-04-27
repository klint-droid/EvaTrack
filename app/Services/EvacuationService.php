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

    /**
     * QR / normal verification for existing registered households.
     * This expects household_members to already exist.
     */
    public function handleScan($householdId, $centerId, $userId, $method = 'qr', $eventId = null)
    {
        return DB::connection('mysql_v2')->transaction(function () use (
            $householdId,
            $centerId,
            $userId,
            $method,
            $eventId
        ) {
            EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

            $this->ensureNotEvacuated($householdId, $centerId);

            $household = Household::with('members')
                ->where('household_id', $householdId)
                ->firstOrFail();

            if ($household->members->count() < 1) {
                throw new \Exception('This household has no registered members. Please add household members first.');
            }

            $record = $this->createEvacuationRecord(
                $householdId,
                $centerId,
                $userId,
                $household->members->count(),
                $method,
                $eventId
            );

            $this->createEvacuatedMembersFromHousehold($record, $household);

            return [
                'record' => $record->fresh([
                    'household.members',
                    'evacuatedMembers.member',
                    'center',
                    'event',
                    'verifiedBy',
                ]),
                'household' => $household->fresh([
                    'members',
                    'address',
                ]),
            ];
        });
    }

    /**
     * Manual verification for existing household.
     * Uses the same behavior as scan: household members must already exist.
     */
    public function handleManual($householdId, $centerId, $userId, $eventId = null)
    {
        return DB::connection('mysql_v2')->transaction(function () use (
            $householdId,
            $centerId,
            $userId,
            $eventId
        ) {
            EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

            $this->ensureNotEvacuated($householdId, $centerId);

            $household = Household::with('members')
                ->where('household_id', $householdId)
                ->firstOrFail();

            $registeredMemberCount = $household->members->count();

            /*
            * If household members already exist:
            * - use actual registered members
            * - create evacuated_members rows
            *
            * If no household members exist yet:
            * - use declared household.member_count
            * - do not create evacuated_members yet
            * - user will add members later in Household Detail
            */
            if ($registeredMemberCount > 0) {
                $record = $this->createEvacuationRecord(
                    $householdId,
                    $centerId,
                    $userId,
                    $registeredMemberCount,
                    'manual',
                    $eventId
                );

                $this->createEvacuatedMembersFromHousehold($record, $household);
            } else {
                $declaredCount = max(1, (int) $household->member_count);

                $record = $this->createEvacuationRecord(
                    $householdId,
                    $centerId,
                    $userId,
                    $declaredCount,
                    'manual',
                    $eventId
                );
            }

            return [
                'evacuation' => $record->fresh([
                    'household.members',
                    'evacuatedMembers.member',
                    'center',
                    'event',
                    'verifiedBy',
                ]),
                'household' => $household->fresh([
                    'members',
                    'address',
                ]),
            ];
        });
    }

    /**
     * On-site registration flow.
     *
     * This allows:
     * Create household -> admit using declared count -> add actual members later.
     *
     * It creates the evacuation_record immediately, but does not create
     * evacuated_members yet because actual household_members do not exist yet.
     */
    public function handleManualWithCount($householdId, $centerId, $userId, $count, $eventId = null)
    {
        return DB::connection('mysql_v2')->transaction(function () use (
            $householdId,
            $centerId,
            $userId,
            $count,
            $eventId
        ) {
            EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

            $this->ensureNotEvacuated($householdId, $centerId);

            $household = Household::where('household_id', $householdId)
                ->firstOrFail();

            $declaredCount = max(1, (int) $count);

            $household->update([
                'member_count' => $declaredCount,
            ]);

            $record = $this->createEvacuationRecord(
                $householdId,
                $centerId,
                $userId,
                $declaredCount,
                'manual',
                $eventId
            );

            return [
                'evacuation' => $record->fresh([
                    'household.members',
                    'evacuatedMembers.member',
                    'center',
                    'event',
                    'verifiedBy',
                ]),
                'household' => $household->fresh([
                    'members',
                    'address',
                ]),
            ];
        });
    }

    private function createEvacuationRecord($householdId, $centerId, $userId, $count, $method, $eventId = null)
    {
        return EvacuationRecord::create([
            'household_id'    => $householdId,
            'center_id'       => $centerId,
            'event_id'        => $this->resolveEventId($eventId, $centerId),
            'status'          => 'evacuated',
            'evacuated_count' => $count,
            'method'          => $method,
            'verified_by'     => $userId,
            'verified_at'     => now(),
        ]);
    }

    private function createEvacuatedMembersFromHousehold(EvacuationRecord $record, Household $household): void
    {
        foreach ($household->members as $member) {
            EvacuatedMember::firstOrCreate(
                [
                    'evacuation_id' => $record->evacuation_id,
                    'member_id'     => $member->member_id,
                ],
                [
                    'verified_at' => now(),
                ]
            );
        }

        $evacuatedCount = EvacuatedMember::where('evacuation_id', $record->evacuation_id)
            ->count();

        $record->update([
            'evacuated_count' => $evacuatedCount,
        ]);
    }

    private function ensureNotEvacuated($householdId, $centerId): void
    {
        $exists = EvacuationRecord::where('household_id', $householdId)
            ->where('center_id', $centerId)
            ->where('status', 'evacuated')
            ->exists();

        if ($exists) {
            throw new \Exception('Household already evacuated in this center.');
        }
    }

    public function generateHouseholdId($type = 'existing')
    {
        $prefix = $type === 'new' ? 'NHH-' : 'HH-';

        do {
            $number = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
            $id = $prefix . $number;
        } while (Household::where('household_id', $id)->exists());

        return $id;
    }
}