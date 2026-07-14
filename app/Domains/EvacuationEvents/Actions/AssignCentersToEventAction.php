<?php

namespace App\Domains\EvacuationEvents\Actions;

use App\Domains\EvacuationEvents\Repositories\EvacuationEventRepositoryInterface;
use App\Domains\EvacuationEvents\Models\DisasterEvent;

class AssignCentersToEventAction
{
    public function __construct(
        private EvacuationEventRepositoryInterface $repository
    ) {}

    public function execute(DisasterEvent $event, array $centerIds): void
    {
        if ($event->ended_at) {
            throw new \Exception('Event already ended');
        }

        $this->repository->assignCenters($event, $centerIds);
    }
}
