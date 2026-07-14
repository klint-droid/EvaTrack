<?php

namespace App\Domains\EvacuationEvents\Actions;

use App\Domains\EvacuationEvents\Repositories\EvacuationEventRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ListDisasterEventsAction
{
    public function __construct(
        private EvacuationEventRepositoryInterface $repository
    ) {}

    public function execute(): Collection
    {
        return $this->repository->getAllEvents();
    }
}
