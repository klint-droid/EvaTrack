<?php

namespace App\Domains\AccommodationUnits\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;

class UnassignUnitAllocationAction
{
    public function __construct(
        private AccommodationUnitRepositoryInterface $repository
    ) {}

    public function execute(int $unitId, int $allocationId): void
    {
        $this->repository->unassignHousehold($unitId, $allocationId);
    }
}
