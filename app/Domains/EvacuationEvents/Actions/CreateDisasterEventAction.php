<?php

namespace App\Domains\EvacuationEvents\Actions;

use App\Domains\EvacuationEvents\Repositories\EvacuationEventRepositoryInterface;
use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\EvacuationEvents\DTOs\DisasterEventDTO;

class CreateDisasterEventAction
{
    public function __construct(
        private EvacuationEventRepositoryInterface $repository
    ) {}

    public function execute(DisasterEventDTO $dto): DisasterEvent
    {
        return $this->repository->create($dto->toArray());
    }
}
