<?php

namespace App\Domains\AccommodationUnits\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;
use App\Domains\AccommodationUnits\Models\AccommodationUnit;
use App\Domains\AccommodationUnits\DTOs\AccommodationUnitDTO;

class CreateAccommodationUnitAction
{
    public function __construct(
        private AccommodationUnitRepositoryInterface $repository
    ) {}

    public function execute(string $centerId, AccommodationUnitDTO $dto): AccommodationUnit
    {
        return $this->repository->createUnit($centerId, $dto->toArray());
    }
}
