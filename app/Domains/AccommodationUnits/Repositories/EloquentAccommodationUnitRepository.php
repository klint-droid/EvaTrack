<?php

namespace App\Domains\AccommodationUnits\Repositories;

use App\Domains\AccommodationUnits\Models\AccommodationUnit;
use App\Domains\AccommodationUnits\Models\AccommodationType;
use App\Domains\AccommodationUnits\Models\UnitAllocation;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Households\Models\HouseholdStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class EloquentAccommodationUnitRepository implements AccommodationUnitRepositoryInterface
{
    private function getEvacuatedStatusIds(): array
    {
        HouseholdStatus::ensureSeeded();
        $byKey = HouseholdStatus::where('status_key', 'evacuated')->value('status_id');
        return array_values(array_unique(array_filter([
            HouseholdStatus::EVACUATED,
            $byKey ? (int) $byKey : null,
        ])));
    }

    public function getUnitsByCenter(string $centerId, int $perPage = 15): LengthAwarePaginator
    {
        $evacuatedStatusIds = $this->getEvacuatedStatusIds();

        $units = AccommodationUnit::with('type')
            ->where('center_id', $centerId)
            ->paginate($perPage);

        $units->getCollection()->transform(function ($unit) use ($evacuatedStatusIds) {
            $activeAllocations = UnitAllocation::with([
                'evacuationRecord.household',
                'assigner',
            ])
                ->where('unit_id', $unit->unit_id)
                ->whereHas('evacuationRecord', function ($er) use ($evacuatedStatusIds) {
                    $er->whereIn('household_status_id', $evacuatedStatusIds)
                       ->where(function ($q) {
                           $q->whereNull('event_id')
                             ->orWhereHas('event', function ($eq) {
                                 $eq->whereNull('ended_at');
                             });
                       });
                })
                ->get();

            $occupancy = (int) $activeAllocations->sum(function ($alloc) {
                return (int) ($alloc->evacuationRecord?->evacuated_count ?? 0);
            });

            $unit->current_occupancy = $occupancy;
            $unit->assigned_households_count = $activeAllocations->count();
            $unit->setRelation('unitAllocations', $activeAllocations);

            return $unit;
        });

        return $units;
    }

    public function getAllTypes(): Collection
    {
        if (AccommodationType::count() === 0) {
            AccommodationType::upsert([
                ['type_id' => 1, 'type_key' => 'family_room',   'type_label' => 'Family Room'],
                ['type_id' => 2, 'type_key' => 'dormitory',     'type_label' => 'Dormitory'],
                ['type_id' => 3, 'type_key' => 'tent',          'type_label' => 'Tent'],
                ['type_id' => 4, 'type_key' => 'classroom',     'type_label' => 'Classroom'],
                ['type_id' => 5, 'type_key' => 'gymnasium',     'type_label' => 'Gymnasium'],
                ['type_id' => 6, 'type_key' => 'covered_court', 'type_label' => 'Covered Court'],
            ], ['type_id'], ['type_key', 'type_label']);
        }

        return AccommodationType::all();
    }

    public function createUnit(string $centerId, array $data): AccommodationUnit
    {
        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

        if ($data['max_capacity'] > $center->capacity) {
            throw new Exception("Unit capacity ({$data['max_capacity']}) cannot exceed center capacity ({$center->capacity}).");
        }

        $existingTotal = AccommodationUnit::where('center_id', $centerId)
            ->whereNull('deleted_at')
            ->sum('max_capacity');
            
        $newTotal = $existingTotal + $data['max_capacity'];

        if ($newTotal > $center->capacity) {
            throw new Exception("Total unit capacity would be {$newTotal}, exceeding center capacity of {$center->capacity}. Available remaining: " . ($center->capacity - $existingTotal) . ".");
        }

        $data['center_id'] = $centerId;
        $data['created_at'] = now();

        return AccommodationUnit::create($data);
    }

    public function updateUnit(int $unitId, string $centerId, array $data): AccommodationUnit
    {
        $unit = AccommodationUnit::where('unit_id', $unitId)
            ->where('center_id', $centerId)
            ->firstOrFail();

        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

        if (isset($data['max_capacity'])) {
            if ($data['max_capacity'] > $center->capacity) {
                throw new Exception("Unit capacity ({$data['max_capacity']}) cannot exceed center capacity ({$center->capacity}).");
            }

            $existingTotal = AccommodationUnit::where('center_id', $centerId)
                ->where('unit_id', '!=', $unitId)
                ->whereNull('deleted_at')
                ->sum('max_capacity');

            $newTotal = $existingTotal + $data['max_capacity'];

            if ($newTotal > $center->capacity) {
                throw new Exception("Total unit capacity would be {$newTotal}, exceeding center capacity of {$center->capacity}. Available remaining: " . ($center->capacity - $existingTotal) . ".");
            }
        }

        $unit->update($data);
        return $unit;
    }

    public function deleteUnit(int $unitId, string $centerId): void
    {
        $unit = AccommodationUnit::where('unit_id', $unitId)
            ->where('center_id', $centerId)
            ->firstOrFail();

        $evacuatedStatusIds = $this->getEvacuatedStatusIds();

        $occupancy = UnitAllocation::where('unit_id', $unit->unit_id)
                ->join('evacuation_records', 'unit_allocations.evacuation_id', '=', 'evacuation_records.evacuation_id')
                ->whereIn('evacuation_records.household_status_id', $evacuatedStatusIds)
                ->sum('evacuation_records.evacuated_count');

        if ($occupancy > 0) {
            throw new Exception('Cannot delete a unit with current occupants. Unassign all households first.');
        }

        $unit->delete();
    }

    public function getAllocationsByUnit(int $unitId): Collection
    {
        $evacuatedStatusIds = $this->getEvacuatedStatusIds();

        return UnitAllocation::with([
            'evacuationRecord.household',
            'assigner'           
        ])
        ->where('unit_id', $unitId)
        ->whereHas('evacuationRecord', function ($er) use ($evacuatedStatusIds) {
            $er->whereIn('household_status_id', $evacuatedStatusIds)
               ->where(function ($q) {
                   $q->whereNull('event_id')
                     ->orWhereHas('event', function ($eq) {
                         $eq->whereNull('ended_at');
                     });
               });
        })
        ->get();
    }

    public function assignHousehold(int $unitId, int $evacuationId, string $assignedByUserId): UnitAllocation
    {
        $evacuatedStatusIds = $this->getEvacuatedStatusIds();

        return DB::connection('mysql_v2')->transaction(function () use ($unitId, $evacuationId, $assignedByUserId, $evacuatedStatusIds) {
            $unit = AccommodationUnit::where('unit_id', $unitId)->firstOrFail();

            $evacuation = EvacuationRecord::where('evacuation_id', $evacuationId)->first();

            if (!$evacuation) {
                throw new Exception('Evacuation record not found.');
            }

            // If the selected unit ID came from a cached center list, match the equivalent unit in the evacuation record's center if possible
            if (strcasecmp(trim((string) $evacuation->center_id), trim((string) $unit->center_id)) !== 0) {
                $matchingUnit = AccommodationUnit::where('center_id', $evacuation->center_id)
                    ->where('name', $unit->name)
                    ->whereNull('deleted_at')
                    ->first();

                if ($matchingUnit) {
                    $unit = $matchingUnit;
                    $unitId = (int) $unit->unit_id;
                } else {
                    throw new Exception("Selected unit ({$unit->name}) does not belong to this evacuation center.");
                }
            }

            if (!in_array((int) $evacuation->household_status_id, $evacuatedStatusIds, true)) {
                if ((int) $evacuation->household_status_id === HouseholdStatus::CHECKED_OUT) {
                    throw new Exception('Cannot assign a checked-out household to an accommodation unit.');
                }
                $evacuation->update(['household_status_id' => HouseholdStatus::EVACUATED]);
            }

            if ($evacuation->event && $evacuation->event->ended_at) {
                throw new Exception('Cannot assign household. The evacuation event has already ended.');
            }

            // Clean up any stale allocations for checked-out or ended-event records on this unit
            $staleAllocationIds = UnitAllocation::where('unit_id', $unitId)
                ->whereDoesntHave('evacuationRecord', function ($er) use ($evacuatedStatusIds) {
                    $er->whereIn('household_status_id', $evacuatedStatusIds)
                       ->where(function ($q) {
                           $q->whereNull('event_id')
                             ->orWhereHas('event', function ($eq) {
                                 $eq->whereNull('ended_at');
                             });
                       });
                })
                ->pluck('allocation_id')
                ->toArray();

            if (!empty($staleAllocationIds)) {
                UnitAllocation::whereIn('allocation_id', $staleAllocationIds)->delete();
            }

            $existingAllocation = UnitAllocation::where('evacuation_id', $evacuationId)
                ->whereHas('unit', function ($uq) use ($unit) {
                    $uq->where('center_id', $unit->center_id);
                })
                ->first();

            if ($existingAllocation && (int) $existingAllocation->unit_id === (int) $unitId) {
                return $existingAllocation;
            }

            $currentOccupancy = (int) UnitAllocation::where('unit_id', $unitId)
                ->where('unit_allocations.evacuation_id', '!=', $evacuationId)
                ->join('evacuation_records', 'unit_allocations.evacuation_id', '=', 'evacuation_records.evacuation_id')
                ->leftJoin('disaster_events', 'evacuation_records.event_id', '=', 'disaster_events.event_id')
                ->whereIn('evacuation_records.household_status_id', $evacuatedStatusIds)
                ->where(function ($q) {
                    $q->whereNull('evacuation_records.event_id')
                      ->orWhere(function ($eq) {
                          $eq->whereNull('disaster_events.ended_at')
                             ->whereNull('disaster_events.deleted_at');
                      });
                })
                ->sum('evacuation_records.evacuated_count');

            $availableCapacity = $unit->max_capacity - $currentOccupancy;

            if ($availableCapacity <= 0) {
                throw new Exception('This unit is already full.');
            }

            if ($evacuation->evacuated_count > $availableCapacity) {
                throw new Exception("Not enough space. This household has {$evacuation->evacuated_count} members but only {$availableCapacity} slots are available.");
            }

            if ($existingAllocation) {
                $existingAllocation->delete();
            }

            return UnitAllocation::create([
                'evacuation_id'        => $evacuationId,
                'unit_id'              => $unitId,
                'assigned_by'          => $assignedByUserId,
                'selected_by_resident' => false,
            ]);
        });
    }

    public function unassignHousehold(int $unitId, int $allocationId): void
    {
        DB::connection('mysql_v2')->transaction(function () use ($unitId, $allocationId) {
            $allocation = UnitAllocation::where('allocation_id', $allocationId)
                ->where('unit_id', $unitId)
                ->firstOrFail();
            $allocation->delete();
        });
    }

    public function getUnassignedEvacuations(string $centerId): Collection
    {
        $evacuatedStatusIds = $this->getEvacuatedStatusIds();

        $assignedIds = UnitAllocation::join('accommodation_units', 'unit_allocations.unit_id', '=', 'accommodation_units.unit_id')
            ->where('accommodation_units.center_id', $centerId)
            ->pluck('unit_allocations.evacuation_id')
            ->toArray();

        return EvacuationRecord::with('household')
            ->where('center_id', $centerId)
            ->whereIn('household_status_id', $evacuatedStatusIds)
            ->where(function ($q) {
                $q->whereNull('event_id')
                  ->orWhereHas('event', function ($eq) {
                      $eq->whereNull('ended_at');
                  });
            })
            ->when(!empty($assignedIds), function ($q) use ($assignedIds) {
                $q->whereNotIn('evacuation_id', $assignedIds);
            })
            ->get();
    }
}
