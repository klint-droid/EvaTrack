<?php

namespace App\Services;

use App\Models\EvacuationCenter;
use App\Models\EvacuationRecord;
use App\Models\HouseholdStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class EvacuationCenterService
{
    public function getAllCentersWithOccuppancy(): Collection
    {
        return Cache::remember('all_centers_occupancy', 60, function () {
            return EvacuationCenter::selectRaw("
                    evacuation_centers.*,
                    (
                        SELECT COUNT(*)
                        FROM evacuation_records
                        JOIN disaster_events ON evacuation_records.event_id = disaster_events.event_id
                        WHERE evacuation_records.center_id = evacuation_centers.evacuation_center_id
                          AND evacuation_records.household_status_id = " . HouseholdStatus::EVACUATED . "
                          AND disaster_events.ended_at IS NULL
                    ) as household_count,
                    (
                        SELECT COALESCE(SUM(evacuation_records.evacuated_count), 0)
                        FROM evacuation_records
                        JOIN disaster_events ON evacuation_records.event_id = disaster_events.event_id
                        WHERE evacuation_records.center_id = evacuation_centers.evacuation_center_id
                          AND evacuation_records.household_status_id = " . HouseholdStatus::EVACUATED . "
                          AND disaster_events.ended_at IS NULL
                    ) as current_occupancy
                ")
                ->with('currentEvent')
                ->get();
        });
    }

    public function create(array $data): EvacuationCenter
    {
        Cache::forget('all_centers_occupancy');
        return EvacuationCenter::create([
            'name'        => $data['name'],
            'osm_address' => $data['osm_address'] ?? null,
            'latitude'    => $data['latitude'],
            'longitude'   => $data['longitude'],
            'capacity'    => $data['capacity'],
        ]);
    }

    public function update(EvacuationCenter $center, array $data): EvacuationCenter
    {
        Cache::forget('all_centers_occupancy');
        $center->update([
            'name'        => $data['name'] ?? $center->name,
            'osm_address' => $data['osm_address'] ?? $center->osm_address,
            'latitude'    => $data['latitude'] ?? $center->latitude,
            'longitude'   => $data['longitude'] ?? $center->longitude,
            'capacity'    => $data['capacity'] ?? $center->capacity,
        ]);

        return $center->fresh();
    }

    public function delete(EvacuationCenter $center): void
    {
        Cache::forget('all_centers_occupancy');
        $center->delete();
    }

    public function getCapacityInfo(EvacuationCenter $center): array
    {
        $current = EvacuationRecord::where('center_id', $center->evacuation_center_id)
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->whereHas('event', function ($q) {
                $q->whereNull('ended_at');
            })
            ->count();

        return [
            'capacity' => $center->capacity,
            'current_occupancy'  => $current,
            'available_capacity' => max(0, $center->capacity - $current),
        ];
    }
}
