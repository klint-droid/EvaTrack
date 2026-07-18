<?php

namespace App\Domains\EvacuationEvents\Actions;

use App\Domains\EvacuationEvents\Repositories\EvacuationEventRepositoryInterface;

class UnassignCenterFromEventAction
{
    public function __construct(
        private EvacuationEventRepositoryInterface $repository
    ) {}

    public function execute(string $centerId): void
    {
        $this->repository->unassignCenter($centerId);
    }
}
