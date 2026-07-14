<?php

namespace App\Domains\AccommodationUnits\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ListUnitAllocationsAction
{
    public function __construct(
        private AccommodationUnitRepositoryInterface $repository
    ) {}

    public function execute(int $unitId): Collection
    {
        return $this->repository->getAllocationsByUnit($unitId);
    }
}
