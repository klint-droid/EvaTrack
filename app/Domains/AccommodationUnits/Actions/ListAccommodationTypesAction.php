<?php

namespace App\Domains\AccommodationUnits\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ListAccommodationTypesAction
{
    public function __construct(
        private AccommodationUnitRepositoryInterface $repository
    ) {}

    public function execute(): Collection
    {
        return $this->repository->getAllTypes();
    }
}
