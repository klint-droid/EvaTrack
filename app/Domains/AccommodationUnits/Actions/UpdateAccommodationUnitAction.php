<?php

namespace App\Domains\AccommodationUnits\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;
use App\Domains\AccommodationUnits\Models\AccommodationUnit;
use App\Domains\AccommodationUnits\DTOs\AccommodationUnitDTO;

class UpdateAccommodationUnitAction
{
    public function __construct(
        private AccommodationUnitRepositoryInterface $repository
    ) {}

    public function execute(int $unitId, string $centerId, AccommodationUnitDTO $dto): AccommodationUnit
    {
        return $this->repository->updateUnit($unitId, $centerId, $dto->toArray());
    }
}
