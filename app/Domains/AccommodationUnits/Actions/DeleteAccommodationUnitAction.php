<?php

namespace App\Domains\AccommodationUnits\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;

class DeleteAccommodationUnitAction
{
    public function __construct(
        private AccommodationUnitRepositoryInterface $repository
    ) {}

    public function execute(int $unitId, string $centerId): void
    {
        $this->repository->deleteUnit($unitId, $centerId);
    }
}
