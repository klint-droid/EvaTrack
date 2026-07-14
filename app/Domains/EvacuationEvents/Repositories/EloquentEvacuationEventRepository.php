<?php

namespace App\Domains\EvacuationEvents\Repositories;

use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\EvacuationEvents\DTOs\EventFilterDTO;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Evacuations\Models\EvacuationRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EloquentEvacuationEventRepository implements EvacuationEventRepositoryInterface
{
    public function getAllEvents(): Collection
    {
        return DisasterEvent::with(['primaryType', 'severity', 'evacuationCenters', 'historicalCenters'])
            ->latest('started_at')
            ->get();
    }

    public function getHistoricalEvents(EventFilterDTO $filter): LengthAwarePaginator
    {
        $query = DisasterEvent::with(['primaryType', 'severity', 'evacuationCenters', 'historicalCenters'])
            ->whereNotNull('ended_at');

        if ($filter->typeId !== null) {
            $query->where('type_id', $filter->typeId);
        }

        if ($filter->startDate !== null) {
            $query->whereDate('started_at', '>=', $filter->startDate);
        }

        if ($filter->endDate !== null) {
            $query->whereDate('ended_at', '<=', $filter->endDate);
        }

        return $query->latest('started_at')->paginate(10);
    }

    public function getActiveEvent(): ?DisasterEvent
    {
        return DisasterEvent::with(['primaryType', 'types', 'evacuationCenters'])
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }

    public function getPublicActiveEvents(): Collection
    {
        return DisasterEvent::with(['primaryType', 'severity', 'types', 'evacuationCenters', 'historicalCenters'])
            ->whereNull('ended_at')
            ->latest('started_at')
            ->get();
    }

    public function create(array $data): DisasterEvent
    {
        return DisasterEvent::create($data);
    }

    public function endEvent(DisasterEvent $event): DisasterEvent
    {
        return DB::connection('mysql_v2')->transaction(function () use ($event) {
            EvacuationCenter::where('current_event_id', $event->event_id)->update(['current_event_id' => null]);
            Cache::forget('all_centers_occupancy');

            EvacuationRecord::where('event_id', $event->event_id)
                ->where('household_status_id', 2) // EVACUATED
                ->update([
                    'household_status_id' => 6, // CHECKED_OUT
                    'updated_at' => now()
                ]);

            $event->update(['ended_at' => now()]);

            return $event;
        });
    }

    public function assignCenters(DisasterEvent $event, array $centerIds): void
    {
        EvacuationCenter::whereIn('evacuation_center_id', $centerIds)
            ->update([
                'current_event_id' => $event->event_id
            ]);
            
        $event->historicalCenters()->syncWithoutDetaching($centerIds);
        
        Cache::forget('all_centers_occupancy');
    }

    public function unassignCenter(int $centerId): void
    {
        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();
        $event_id = $center->current_event_id;

        $center->update([
            'current_event_id' => null
        ]);
        
        if ($event_id) {
            $event = DisasterEvent::find($event_id);
            if ($event && !$event->ended_at) {
                $event->historicalCenters()->detach($centerId);
            }
        }
        
        Cache::forget('all_centers_occupancy');
    }
}
