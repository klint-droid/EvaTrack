<?php

namespace App\Domains\ResourceRequests\Actions;

use App\Domains\ResourceRequests\Repositories\ResourceRequestRepositoryInterface;
use App\Domains\ResourceRequests\Models\ResourceRequest;

class GetResourceRequestAction
{
    public function __construct(
        private ResourceRequestRepositoryInterface $repository
    ) {}

    public function execute(string $id, ?int $enforcedCenterId = null): ?ResourceRequest
    {
        return $this->repository->getRequestById($id, $enforcedCenterId);
    }
}
