<?php

namespace App\Services;

use App\Models\EvacuationRecord;
use App\Models\EvacuationCenter;
use Illuminate\Support\Str;

class EvacuationService
{
    public function handleScan($householdId, $evacuationCenterId, $userId, $method = 'qr')
    {
        $center = EvacuationCenter::where('evacuation_center_id', $evacuationCenterId)
            ->firstOrFail();

        // ✅ Capacity check
        if ($center->current_occupancy >= $center->capacity) {
            throw new \Exception("Evacuation center is full");
        }

        // ✅ Check existing record
        $record = EvacuationRecord::where('household_id', $householdId)->first();

        // 🆕 CREATE
        if (!$record) {
            $record = EvacuationRecord::create([
                'evacuation_id' => Str::uuid(),
                'household_id' => $householdId,
                'evacuation_center_id' => $evacuationCenterId,
                'room_assignment_id' => null,
                'status' => 'evacuated',
                'is_verified' => 1,
                'verified_by' => $userId,
                'method' => $method,
                'verified_at' => now(),
            ]);

            $center->increment('current_occupancy');
        }

        // 🔄 UPDATE (if not yet evacuated)
        else if ($record->status !== 'evacuated') {
            $record->update([
                'status' => 'evacuated',
                'evacuation_center_id' => $evacuationCenterId,
                'is_verified' => 1,
                'verified_by' => $userId,
                'method' => 'qr',
                'verified_at' => now(),
            ]);

            $center->increment('current_occupancy');
        }

        // ⚠️ Already evacuated
        else {
            throw new \Exception("Household already evacuated");
        }

        return $record;
    }

    public function handleManual($household, $centerId, $userId){
        return $this->handleScan($household, $centerId, $userId, 'manual');
    }

    public function generateHouseholdId($type = 'existing'){
        $prefix = $type === 'new' ? 'NHH-' : 'HH-';
        
        do{
            $number = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
            $id = $prefix . $number;
        } while (Household::where('household_id', $id)->exists());

        return $id;
    }

    public function handleNewHousehold($household_name, $centerId, $userId){
        $household = Household::create([
            'household_id' => $this->generateHouseholdId('new'),
            'household_name' => $household_name,
        ]);

        return $this->handleScan($household->household_id, $centerId, $userId, 'manual');
    }
}