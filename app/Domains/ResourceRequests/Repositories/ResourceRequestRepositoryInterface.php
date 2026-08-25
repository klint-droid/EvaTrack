<?php

namespace App\Domains\ResourceRequests\Repositories;

use App\Domains\ResourceRequests\Models\ResourceRequest;
use App\Domains\ResourceRequests\DTOs\ResourceRequestFilterDTO;
use Illuminate\Database\Eloquent\Collection;

interface ResourceRequestRepositoryInterface
{
    /**
     * Get a paginated or limited list of requests with summary stats.
     * 
     * @param ResourceRequestFilterDTO $filter
     * @param int|null $enforcedCenterId If set, scopes the query to this center.
     * @return array ['data' => Collection, 'summary' => array]
     */
    public function getFilteredRequests(ResourceRequestFilterDTO $filter, ?int $enforcedCenterId = null): array;

    public function getRequestById(string $id, ?int $enforcedCenterId = null): ?ResourceRequest;

    public function createRequest(array $data): ResourceRequest;

    public function updateStatus(string $id, string $statusKey, string $handlerUserId): ResourceRequest;

    public function deleteRequest(ResourceRequest $request): void;
}
