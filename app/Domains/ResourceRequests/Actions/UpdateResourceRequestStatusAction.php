<?php

namespace App\Domains\ResourceRequests\Actions;

use App\Domains\ResourceRequests\Repositories\ResourceRequestRepositoryInterface;
use App\Domains\ResourceRequests\Models\ResourceRequest;

class UpdateResourceRequestStatusAction
{
    public function __construct(
        private ResourceRequestRepositoryInterface $repository
    ) {}

    public function execute(string $id, string $statusKey, string $handlerUserId): ResourceRequest
    {
        return $this->repository->updateStatus($id, $statusKey, $handlerUserId);
    }
}
