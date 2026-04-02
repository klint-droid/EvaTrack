<?php

namespace App\Services;

use App\Models\Evacuation;
use App\Models\EvacuationCenter;

class EvacuationService
{
    public function handleScan($householdId, $evacuationCenterId, $userId){
        $evacuationCenter = EvacuationCenter::where('evacuation_center_id', $evacuationCenterId)->firstOrFail();

        //capacity check
        if($evacuationCenter->current_occupancy >= $evacuationCenter->capacity){
            throw new \Exception("Evacuation center is full");
        }

        $evacuation = Evacuation::where('household_id', $householdId)->first();

        //create

        if(!$evacuation){
            $evacuation = Evacuation::create([
                'household_id' => $householdId,
                'evacuation_center_id' => $evacuationCenterId,
                'status' => 'evacuated',
                'evacuated_at' => now(),
                'processed_by' => $userId
            ]);

            $evacuationCenter->increment('current_occupancy');
        }

        //update

        else if ($evacuation->status !== 'evacuated'){
            $evacuation->update([
                'status' => 'evacuated',
                'evacuated_at' => now(),
                'evacation_center_id' => $evacuationCenterId,
                'processed_by' => $userId
            ]);

            $evacuationCenter->increment('current_occupancy');
        }

        return $evacuation;
    }   
}
