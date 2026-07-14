<?php

namespace App\Domains\EvacuationEvents\Actions;

use App\Domains\EvacuationEvents\Repositories\EvacuationEventRepositoryInterface;
use App\Domains\EvacuationEvents\Models\DisasterEvent;

class EndDisasterEventAction
{
    public function __construct(
        private EvacuationEventRepositoryInterface $repository
    ) {}

    public function execute(DisasterEvent $event): DisasterEvent
    {
        if ($event->ended_at) {
            throw new \Exception('Event already ended');
        }

        return $this->repository->endEvent($event);
    }
}
