<?php

namespace App\Domains\AccommodationUnits\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GetUnassignedEvacuationsAction
{
    public function __construct(
        private AccommodationUnitRepositoryInterface $repository
    ) {}

    public function execute(int $centerId): Collection
    {
        return $this->repository->getUnassignedEvacuations($centerId);
    }
}
