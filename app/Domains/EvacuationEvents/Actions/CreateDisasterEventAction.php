<?php

namespace App\Domains\EvacuationEvents\Actions;

use App\Domains\EvacuationEvents\Repositories\EvacuationEventRepositoryInterface;
use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\EvacuationEvents\DTOs\DisasterEventDTO;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;

class CreateDisasterEventAction
{
    public function __construct(
        private EvacuationEventRepositoryInterface $repository
    ) {}

    public function execute(DisasterEventDTO $dto): DisasterEvent
    {
        $event = $this->repository->create($dto->toArray());

        // Auto-assign all evacuation centers to the new event
        $allCenterIds = EvacuationCenter::pluck('evacuation_center_id')->toArray();
        if (!empty($allCenterIds)) {
            $this->repository->assignCenters($event, $allCenterIds);
        }

        return $event;
    }
}
