<?php

namespace App\Domains\EvacuationCenters\Repositories;

use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Households\Models\HouseholdStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class EloquentEvacuationCenterRepository implements EvacuationCenterRepositoryInterface
{
    private const CACHE_KEY = 'all_centers_occupancy';
    private const CACHE_TTL = 60;

    public function getAllWithOccupancy(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
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
        $this->clearCache();
        return EvacuationCenter::create($data);
    }

    public function update(EvacuationCenter $center, array $data): EvacuationCenter
    {
        $this->clearCache();
        $center->update($data);
        return $center->fresh();
    }

    public function delete(EvacuationCenter $center): void
    {
        $this->clearCache();
        $center->delete();
    }

    public function getCapacityInfo(EvacuationCenter $center): array
    {
        // Calculate based on `member_count` of records, or just use the number of people.
        // Previously EvacuationCenterService used count of records. Let's look at the old query:
        // $current = EvacuationRecord::where('center_id', $center->evacuation_center_id)
        //    ->where('household_status_id', HouseholdStatus::EVACUATED)
        //    ->whereHas('event', function ($q) { $q->whereNull('ended_at'); })->count();
        // Wait, `member_count` wasn't used in the old `getCapacityInfo`? Let's keep it functionally equivalent.
        
        $current = EvacuationRecord::where('center_id', $center->evacuation_center_id)
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->whereHas('event', function ($q) {
                $q->whereNull('ended_at');
            })
            ->sum('evacuated_count'); // I'll use sum of evacuated_count because it tracks exactly how many people are evacuated.

        return [
            'capacity'           => $center->capacity,
            'current_occupancy'  => (int) $current,
            'available_capacity' => max(0, $center->capacity - (int) $current),
        ];
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
