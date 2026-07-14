<?php

namespace App\Domains\EvacuationEvents\Actions;

use App\Domains\EvacuationEvents\Repositories\EvacuationEventRepositoryInterface;
use App\Domains\EvacuationEvents\Models\DisasterEvent;

class GetActiveEventAction
{
    public function __construct(
        private EvacuationEventRepositoryInterface $repository
    ) {}

    public function execute(): ?DisasterEvent
    {
        return $this->repository->getActiveEvent();
    }
}
