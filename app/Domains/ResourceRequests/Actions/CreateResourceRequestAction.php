<?php

namespace App\Domains\ResourceRequests\Actions;

use App\Domains\ResourceRequests\Repositories\ResourceRequestRepositoryInterface;
use App\Domains\ResourceRequests\DTOs\ResourceRequestDTO;
use App\Domains\ResourceRequests\Models\ResourceRequest;

class CreateResourceRequestAction
{
    public function __construct(
        private ResourceRequestRepositoryInterface $repository
    ) {}

    public function execute(ResourceRequestDTO $dto, int $requesterUserId, ?int $enforcedCenterId = null): ResourceRequest
    {
        $data = $dto->toArray();
        $data['requested_by'] = $requesterUserId;
        
        if ($enforcedCenterId !== null) {
            $data['evacuation_center_id'] = $enforcedCenterId;
        }

        return $this->repository->createRequest($data);
    }
}
