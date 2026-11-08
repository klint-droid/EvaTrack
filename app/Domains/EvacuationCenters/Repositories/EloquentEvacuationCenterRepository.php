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
        HouseholdStatus::ensureSeeded();

        EvacuationCenter::whereNotNull('current_event_id')
            ->whereDoesntHave('currentEvent', function ($q) {
                $q->whereNull('ended_at');
            })
            ->update(['current_event_id' => null]);

        return EvacuationCenter::selectRaw("
                evacuation_centers.*,
                (
                    SELECT COUNT(DISTINCT evacuation_records.household_id)
                    FROM evacuation_records
                    LEFT JOIN disaster_events ON evacuation_records.event_id = disaster_events.event_id
                    WHERE evacuation_records.center_id = evacuation_centers.evacuation_center_id
                      AND evacuation_records.household_status_id = " . HouseholdStatus::EVACUATED . "
                      AND (
                          evacuation_records.event_id IS NULL
                          OR (disaster_events.ended_at IS NULL AND disaster_events.deleted_at IS NULL)
                      )
                ) as household_count,
                (
                    SELECT COALESCE(SUM(evacuation_records.evacuated_count), 0)
                    FROM evacuation_records
                    LEFT JOIN disaster_events ON evacuation_records.event_id = disaster_events.event_id
                    WHERE evacuation_records.center_id = evacuation_centers.evacuation_center_id
                      AND evacuation_records.household_status_id = " . HouseholdStatus::EVACUATED . "
                      AND (
                          evacuation_records.event_id IS NULL
                          OR (disaster_events.ended_at IS NULL AND disaster_events.deleted_at IS NULL)
                      )
                ) as current_occupancy
            ")
            ->with('currentEvent')
            ->get();
    }

    public function create(array $data): EvacuationCenter
    {
        $this->clearCache();

        // If an active disaster event is currently ongoing, automatically attach new center
        if (empty($data['current_event_id'])) {
            $activeEvent = \App\Domains\EvacuationEvents\Models\DisasterEvent::whereNull('ended_at')->latest('started_at')->first();
            if ($activeEvent) {
                $data['current_event_id'] = $activeEvent->event_id;
            }
        }

        $center = EvacuationCenter::create($data);

        if (!empty($data['current_event_id'])) {
            $event = \App\Domains\EvacuationEvents\Models\DisasterEvent::find($data['current_event_id']);
            if ($event) {
                $event->historicalCenters()->syncWithoutDetaching([$center->evacuation_center_id]);
            }
        }

        return $center;
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
        HouseholdStatus::ensureSeeded();

        $baseQuery = EvacuationRecord::where('center_id', $center->evacuation_center_id)
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->where(function ($q) {
                $q->whereNull('event_id')
                  ->orWhereHas('event', function ($eq) {
                      $eq->whereNull('ended_at');
                  });
            });

        $current = (int) (clone $baseQuery)->sum('evacuated_count');
        $households = (int) (clone $baseQuery)->distinct('household_id')->count('household_id');

        return [
            'capacity'           => $center->capacity,
            'current_occupancy'  => $current,
            'household_count'    => $households,
            'available_capacity' => max(0, $center->capacity - $current),
        ];
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
