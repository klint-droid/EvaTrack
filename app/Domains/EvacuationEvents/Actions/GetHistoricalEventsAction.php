<?php

namespace App\Domains\EvacuationEvents\Actions;

use App\Domains\EvacuationEvents\Repositories\EvacuationEventRepositoryInterface;
use App\Domains\EvacuationEvents\DTOs\EventFilterDTO;
use Illuminate\Pagination\LengthAwarePaginator;

class GetHistoricalEventsAction
{
    public function __construct(
        private EvacuationEventRepositoryInterface $repository
    ) {}

    public function execute(EventFilterDTO $filter): LengthAwarePaginator
    {
        return $this->repository->getHistoricalEvents($filter);
    }
}
