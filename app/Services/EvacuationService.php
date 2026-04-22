<?php

namespace App\Services;

use App\Models\EvacuationRecord;
use App\Models\EvacuationCenter;
use App\Models\Household;
use App\Models\EvacuatedMember;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EvacuationService
{
    public function handleScan($householdId, $centerId, $userId, $method = 'qr')
    {
        return DB::transaction(function () use ($householdId, $centerId, $userId, $method) {

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
                $method
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

    public function handleManual($householdId, $centerId, $userId)
    {
        return $this->handleScan($householdId, $centerId, $userId, 'manual');
    }

    public function handleManualWithCount($householdId, $centerId, $userId, $count)
    {
        return DB::transaction(function () use ($householdId, $centerId, $userId, $count) {

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
                'manual'
            );

            return [
                'evacuation' => $record,
                'household' => $household
            ];
        });
    }

    private function createEvacuationRecord($householdId, $centerId, $userId, $count, $method)
    {
        return EvacuationRecord::create([
            'evacuation_id' => Str::uuid(),
            'household_id' => $householdId,
            'center_id' => $centerId,
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