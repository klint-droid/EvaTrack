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
    public function getUnitsByCenter(string $centerId, int $perPage = 15): LengthAwarePaginator
    {
        $units = AccommodationUnit::with('type')
            ->where('center_id', $centerId)
            ->paginate($perPage);

        $units->getCollection()->transform(function ($unit) {
            $occupancy = UnitAllocation::where('unit_id', $unit->unit_id)
                ->join('evacuation_records', 'unit_allocations.evacuation_id', '=', 'evacuation_records.evacuation_id')
                ->sum('evacuation_records.evacuated_count');
            
            $unit->current_occupancy = $occupancy;

            return $unit;
        });

        return $units;
    }

    public function getAllTypes(): Collection
    {
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

        $occupancy = UnitAllocation::where('unit_id', $unit->unit_id)
                ->join('evacuation_records', 'unit_allocations.evacuation_id', '=', 'evacuation_records.evacuation_id')
                ->sum('evacuation_records.evacuated_count');

        if ($occupancy > 0) {
            throw new Exception('Cannot delete a unit with current occupants. Unassign all households first.');
        }

        $unit->delete();
    }

    public function getAllocationsByUnit(int $unitId): Collection
    {
        return UnitAllocation::with([
            'evacuationRecord.household',
            'assigner'           
        ])
        ->where('unit_id', $unitId)
        ->get();
    }

    public function assignHousehold(int $unitId, int $evacuationId, string $assignedByUserId): UnitAllocation
    {
        $evacuatedStatusId = HouseholdStatus::where('status_key', 'evacuated')->value('status_id');

        return DB::transaction(function () use ($unitId, $evacuationId, $assignedByUserId, $evacuatedStatusId) {
            $unit = AccommodationUnit::where('unit_id', $unitId)->firstOrFail();

            $evacuation = EvacuationRecord::where('evacuation_id', $evacuationId)
                ->where('center_id', $unit->center_id)
                ->where('household_status_id', $evacuatedStatusId)
                ->first();

            if (!$evacuation) {
                throw new Exception('Evacuation record not found or does not belong to this center.');
            }

            if ($evacuation->event && $evacuation->event->ended_at) {
                throw new Exception('Cannot assign household. The evacuation event has already ended.');
            }

            $alreadyAssigned = UnitAllocation::join('accommodation_units', 'unit_allocations.unit_id', '=', 'accommodation_units.unit_id')
                ->where('accommodation_units.center_id', $unit->center_id)
                ->where('unit_allocations.evacuation_id', $evacuationId)
                ->exists();

            if ($alreadyAssigned) {
                throw new Exception('Household is already assigned to a unit in this center.');
            }

            $currentOccupancy = UnitAllocation::where('unit_id', $unitId)
                ->join('evacuation_records', 'unit_allocations.evacuation_id', '=', 'evacuation_records.evacuation_id')
                ->sum('evacuation_records.evacuated_count');

            $availableCapacity = $unit->max_capacity - $currentOccupancy;

            if ($availableCapacity <= 0) {
                throw new Exception('This unit is already full.');
            }

            if ($evacuation->evacuated_count > $availableCapacity) {
                throw new Exception("Not enough space. This household has {$evacuation->evacuated_count} members but only {$availableCapacity} slots are available.");
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
        DB::transaction(function () use ($unitId, $allocationId) {
            $allocation = UnitAllocation::where('allocation_id', $allocationId)
                ->where('unit_id', $unitId)
                ->firstOrFail();
            $allocation->delete();
        });
    }

    public function getUnassignedEvacuations(string $centerId): Collection
    {
        $evacuatedStatusId = HouseholdStatus::where('status_key', 'evacuated')->value('status_id');

        $assignedIds = UnitAllocation::join('accommodation_units', 'unit_allocations.unit_id', '=', 'accommodation_units.unit_id')
            ->where('accommodation_units.center_id', $centerId)
            ->pluck('unit_allocations.evacuation_id')
            ->toArray();

        return EvacuationRecord::with('household')
            ->where('center_id', $centerId)
            ->where('household_status_id', $evacuatedStatusId)
            ->when(!empty($assignedIds), function ($q) use ($assignedIds) {
                $q->whereNotIn('evacuation_id', $assignedIds);
            })
            ->get();
    }
}
