<?php

namespace App\Domains\AccommodationUnits\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ListAccommodationUnitsAction
{
    public function __construct(
        private AccommodationUnitRepositoryInterface $repository
    ) {}

    public function execute(string $centerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getUnitsByCenter($centerId, $perPage);
    }
}
