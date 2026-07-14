<?php

namespace App\Domains\EvacuationCenters\Actions;

use App\Domains\EvacuationCenters\Repositories\EvacuationCenterRepositoryInterface;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;

class DeleteEvacuationCenterAction
{
    public function __construct(
        private EvacuationCenterRepositoryInterface $repository
    ) {}

    public function execute(EvacuationCenter $center): void
    {
        $this->repository->delete($center);
    }
}
