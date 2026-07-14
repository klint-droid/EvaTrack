<?php

namespace App\Domains\EvacuationCenters\Actions;

use App\Domains\EvacuationCenters\Repositories\EvacuationCenterRepositoryInterface;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\EvacuationCenters\DTOs\EvacuationCenterDTO;

class CreateEvacuationCenterAction
{
    public function __construct(
        private EvacuationCenterRepositoryInterface $repository
    ) {}

    public function execute(EvacuationCenterDTO $dto): EvacuationCenter
    {
        return $this->repository->create($dto->toArray());
    }
}
