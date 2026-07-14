<?php

namespace App\Domains\EvacuationCenters\Actions;

use App\Domains\EvacuationCenters\Repositories\EvacuationCenterRepositoryInterface;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;

class GetCenterCapacityAction
{
    public function __construct(
        private EvacuationCenterRepositoryInterface $repository
    ) {}

    public function execute(EvacuationCenter $center): array
    {
        return $this->repository->getCapacityInfo($center);
    }
}
