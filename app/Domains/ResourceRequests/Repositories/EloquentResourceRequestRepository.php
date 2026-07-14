<?php

namespace App\Domains\ResourceRequests\Repositories;

use App\Domains\ResourceRequests\Models\ResourceRequest;
use App\Domains\ResourceRequests\Models\ResourceRequestStatus;
use App\Domains\ResourceRequests\DTOs\ResourceRequestFilterDTO;
use App\Domains\Notifications\Models\UrgencyLevel;
use Exception;

class EloquentResourceRequestRepository implements ResourceRequestRepositoryInterface
{
    private function getStatusIds(): array
    {
        return ResourceRequestStatus::pluck('status_id', 'status_key')->toArray();
    }

    private function getUrgencyLevelIds(): array
    {
        return UrgencyLevel::pluck('urgency_id', 'urgency_key')->toArray();
    }

    private function requestRelations(): array
    {
        return [
            'center',
            'requester',
            'handler',
            'urgencyLevel',
            'status',
        ];
    }

    public function getFilteredRequests(ResourceRequestFilterDTO $filter, ?int $enforcedCenterId = null): array
    {
        $statusIds = $this->getStatusIds();
        $urgencyLevelIds = $this->getUrgencyLevelIds();

        $query = ResourceRequest::with($this->requestRelations());

        if ($enforcedCenterId !== null) {
            $query->where('evacuation_center_id', $enforcedCenterId);
        } elseif ($filter->centerId !== null) {
            $query->where('evacuation_center_id', $filter->centerId);
        }

        if ($filter->status !== null) {
            $statusId = $statusIds[$filter->status] ?? null;
            if ($statusId) {
                $query->where('status_id', $statusId);
            }
        }

        if ($filter->urgencyId !== null) {
            // Because $filter->urgencyId is already an ID (not a key string like in the controller), 
            // we can just use it directly.
            $query->where('urgency_id', $filter->urgencyId);
        }

        if ($filter->q !== null) {
            $search = $filter->q;
            $query->where(function ($q) use ($search) {
                $q->where('resource_type', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('request_id', 'LIKE', "%{$search}%");
            });
        }

        $summary = [
            'pending'      => (clone $query)->where('status_id', $statusIds[ResourceRequestStatus::PENDING] ?? null)->count(),
            'acknowledged' => (clone $query)->where('status_id', $statusIds[ResourceRequestStatus::ACKNOWLEDGED] ?? null)->count(),
            'approved'     => (clone $query)->where('status_id', $statusIds[ResourceRequestStatus::APPROVED] ?? null)->count(),
            'rejected'     => (clone $query)->where('status_id', $statusIds[ResourceRequestStatus::REJECTED] ?? null)->count(),
            'delivered_24h'=> ResourceRequest::where('status_id', $statusIds[ResourceRequestStatus::DELIVERED] ?? null)
                ->where('updated_at', '>=', now()->subDay())
                ->when($enforcedCenterId !== null, function ($q) use ($enforcedCenterId) {
                    $q->where('evacuation_center_id', $enforcedCenterId);
                })
                ->count(),
        ];

        if ($filter->limit > 0) {
            $query->limit($filter->limit);
        }

        return [
            'data'    => $query->latest('created_at')->get(),
            'summary' => $summary,
        ];
    }

    public function getRequestById(string $id, ?int $enforcedCenterId = null): ?ResourceRequest
    {
        $query = ResourceRequest::with($this->requestRelations())->where('request_id', $id);

        if ($enforcedCenterId !== null) {
            $query->where('evacuation_center_id', $enforcedCenterId);
        }

        return $query->first();
    }

    public function createRequest(array $data): ResourceRequest
    {
        $statusIds = $this->getStatusIds();
        $data['status_id'] = $statusIds[ResourceRequestStatus::PENDING];
        $data['handled_by'] = null;

        $requestRecord = ResourceRequest::create($data);
        return $requestRecord->load($this->requestRelations());
    }

    public function updateStatus(string $id, string $statusKey, int $handlerUserId): ResourceRequest
    {
        $statusIds = $this->getStatusIds();
        
        if (!isset($statusIds[$statusKey])) {
            throw new Exception("The selected status is invalid.");
        }

        $requestRecord = ResourceRequest::where('request_id', $id)->firstOrFail();

        $requestRecord->update([
            'status_id'  => $statusIds[$statusKey],
            'handled_by' => $handlerUserId,
        ]);

        return $requestRecord->fresh($this->requestRelations());
    }

    public function deleteRequest(ResourceRequest $request): void
    {
        $request->delete();
    }
}
