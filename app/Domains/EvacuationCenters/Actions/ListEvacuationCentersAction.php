<?php

namespace App\Domains\EvacuationCenters\Actions;

use App\Domains\EvacuationCenters\Repositories\EvacuationCenterRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ListEvacuationCentersAction
{
    public function __construct(
        private EvacuationCenterRepositoryInterface $repository
    ) {}

    public function execute(): Collection
    {
        return $this->repository->getAllWithOccupancy();
    }
}
