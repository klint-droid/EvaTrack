<?php

namespace App\Domains\AccommodationUnits\Repositories;

use App\Domains\AccommodationUnits\Models\AccommodationUnit;
use App\Domains\AccommodationUnits\Models\UnitAllocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AccommodationUnitRepositoryInterface
{
    public function getUnitsByCenter(string $centerId, int $perPage = 15): LengthAwarePaginator;
    public function getAllTypes(): Collection;
    public function createUnit(string $centerId, array $data): AccommodationUnit;
    public function updateUnit(int $unitId, string $centerId, array $data): AccommodationUnit;
    public function deleteUnit(int $unitId, string $centerId): void;
    
    public function getAllocationsByUnit(int $unitId): Collection;
    public function assignHousehold(int $unitId, int $evacuationId, string $assignedByUserId): UnitAllocation;
    public function unassignHousehold(int $unitId, int $allocationId): void;
    public function getUnassignedEvacuations(string $centerId): Collection;
}
