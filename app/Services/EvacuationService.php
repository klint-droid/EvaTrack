<?php

namespace App\Services;

use App\Models\EvacuationRecord;
use App\Models\EvacuationCenter;
use App\Models\Household;
use App\Models\EvacuatedMember;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\EvacuationEvent;

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

    public function handleScan($householdId, $centerId, $userId, $method = 'qr', $eventId = null)
    {
        return DB::transaction(function () use ($householdId, $centerId, $userId, $method, $eventId) {

            EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

            $this->ensureNotEvacuated($householdId);

            $household = Household::with('members')
                ->where('household_id', $householdId)
                ->firstOrFail();

            $count = $household->members->count();

            $record = $this->createEvacuationRecord(
                $householdId,
                $centerId,
                $userId,
                $count,
                $method,
                $eventId
            );

            foreach ($household->members as $member) {
                EvacuatedMember::create([
                    'evacuated_member_id' => Str::uuid(),
                    'evacuation_id' => $record->evacuation_id,
                    'member_id' => $member->member_id,
                    'verified_at' => now(),
                ]);
            }

            return compact('record', 'household');
        });
    }

    public function handleManual($householdId, $centerId, $userId, $eventId = null)
    {
        return $this->handleScan($householdId, $centerId, $userId, 'manual', $eventId);
    }

    public function handleManualWithCount($householdId, $centerId, $userId, $count, $eventId = null)
    {
        return DB::transaction(function () use ($householdId, $centerId, $userId, $count, $eventId) {

            EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

            $this->ensureNotEvacuated($householdId);

            $household = Household::where('household_id', $householdId)->firstOrFail();

            // ✅ sync household count
            $household->update([
                'member_count' => $count
            ]);

            $record = $this->createEvacuationRecord(
                $householdId,
                $centerId,
                $userId,
                $count,
                'manual',
                $eventId
            );

            return [
                'evacuation' => $record,
                'household' => $household
            ];
        });
    }

    private function createEvacuationRecord($householdId, $centerId, $userId, $count, $method, $eventId = null)
    {
        return EvacuationRecord::create([
            'household_id' => $householdId,
            'center_id' => $centerId,
            'event_id' => $this->resolveEventId($eventId, $centerId),
            'status' => 'evacuated',
            'evacuated_count' => $count,
            'method' => $method,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
    }

    private function ensureNotEvacuated($householdId)
    {
        $exists = EvacuationRecord::where('household_id', $householdId)
            ->where('status', 'evacuated')
            ->exists();

        if ($exists) {
            throw new \Exception("Household already evacuated");
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