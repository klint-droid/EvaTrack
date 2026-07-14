<?php

namespace App\Domains\AccommodationUnits\Repositories;

use App\Domains\AccommodationUnits\Models\AccommodationUnit;
use App\Domains\AccommodationUnits\Models\UnitAllocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AccommodationUnitRepositoryInterface
{
    public function getUnitsByCenter(int $centerId, int $perPage = 15): LengthAwarePaginator;
    public function getAllTypes(): Collection;
    public function createUnit(int $centerId, array $data): AccommodationUnit;
    public function updateUnit(int $unitId, int $centerId, array $data): AccommodationUnit;
    public function deleteUnit(int $unitId, int $centerId): void;
    
    public function getAllocationsByUnit(int $unitId): Collection;
    public function assignHousehold(int $unitId, int $evacuationId, int $assignedByUserId): UnitAllocation;
    public function unassignHousehold(int $unitId, int $allocationId): void;
    public function getUnassignedEvacuations(int $centerId): Collection;
}
