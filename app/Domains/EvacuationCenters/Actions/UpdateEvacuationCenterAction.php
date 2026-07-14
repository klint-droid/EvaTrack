<?php

namespace App\Domains\EvacuationCenters\Actions;

use App\Domains\EvacuationCenters\Repositories\EvacuationCenterRepositoryInterface;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\EvacuationCenters\DTOs\EvacuationCenterDTO;

class UpdateEvacuationCenterAction
{
    public function __construct(
        private EvacuationCenterRepositoryInterface $repository
    ) {}

    public function execute(EvacuationCenter $center, EvacuationCenterDTO $dto): EvacuationCenter
    {
        return $this->repository->update($center, $dto->toArray());
    }
}
