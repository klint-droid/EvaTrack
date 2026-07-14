<?php

namespace App\Domains\ResourceRequests\Actions;

use App\Domains\ResourceRequests\Repositories\ResourceRequestRepositoryInterface;
use App\Domains\ResourceRequests\DTOs\ResourceRequestFilterDTO;

class ListResourceRequestsAction
{
    public function __construct(
        private ResourceRequestRepositoryInterface $repository
    ) {}

    public function execute(ResourceRequestFilterDTO $filter, ?int $enforcedCenterId = null): array
    {
        return $this->repository->getFilteredRequests($filter, $enforcedCenterId);
    }
}
