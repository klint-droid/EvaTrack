<?php

namespace App\Domains\AccommodationUnits\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;
use App\Domains\AccommodationUnits\Models\UnitAllocation;
use App\Domains\AccommodationUnits\DTOs\UnitAllocationDTO;

class AssignUnitAllocationAction
{
    public function __construct(
        private AccommodationUnitRepositoryInterface $repository
    ) {}

    public function execute(int $unitId, UnitAllocationDTO $dto, int $assignedByUserId): UnitAllocation
    {
        return $this->repository->assignHousehold($unitId, $dto->evacuationId, $assignedByUserId);
    }
}
