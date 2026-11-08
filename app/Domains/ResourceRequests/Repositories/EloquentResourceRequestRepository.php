<?php

namespace App\Domains\ResourceRequests\Repositories;

use App\Domains\ResourceRequests\Models\ResourceRequest;
use App\Domains\ResourceRequests\Models\ResourceRequestStatus;
use App\Domains\ResourceRequests\DTOs\ResourceRequestFilterDTO;
use Exception;

class EloquentResourceRequestRepository implements ResourceRequestRepositoryInterface
{
    private function ensureStatusesSeeded(): void
    {
        $defaults = [
            ['status_id' => 1, 'status_key' => ResourceRequestStatus::PENDING,      'status_label' => 'Pending'],
            ['status_id' => 2, 'status_key' => ResourceRequestStatus::ACKNOWLEDGED, 'status_label' => 'Acknowledged'],
            ['status_id' => 3, 'status_key' => ResourceRequestStatus::APPROVED,     'status_label' => 'Approved'],
            ['status_id' => 4, 'status_key' => ResourceRequestStatus::REJECTED,     'status_label' => 'Rejected'],
            ['status_id' => 5, 'status_key' => ResourceRequestStatus::DELIVERED,    'status_label' => 'Delivered'],
        ];

        ResourceRequestStatus::upsert($defaults, ['status_id'], ['status_key', 'status_label']);
    }

    private function getStatusIds(): array
    {
        $statusIds = array_change_key_case(
            ResourceRequestStatus::pluck('status_id', 'status_key')->toArray(),
            CASE_LOWER
        );

        if (
            !isset($statusIds[ResourceRequestStatus::PENDING]) ||
            !isset($statusIds[ResourceRequestStatus::ACKNOWLEDGED]) ||
            !isset($statusIds[ResourceRequestStatus::APPROVED]) ||
            !isset($statusIds[ResourceRequestStatus::REJECTED]) ||
            !isset($statusIds[ResourceRequestStatus::DELIVERED])
        ) {
            $this->ensureStatusesSeeded();
            $statusIds = array_change_key_case(
                ResourceRequestStatus::pluck('status_id', 'status_key')->toArray(),
                CASE_LOWER
            );
        }

        return $statusIds;
    }

    private function requestRelations(): array
    {
        return [
            'center',
            'requester',
            'handler',
            'status',
        ];
    }

    public function getFilteredRequests(ResourceRequestFilterDTO $filter, int|string|null $enforcedCenterId = null): array
    {
        $statusIds = $this->getStatusIds();

        $query = ResourceRequest::with($this->requestRelations());

        $query->where('request_id', 'LIKE', 'REQ%');

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

    public function getRequestById(string $id, int|string|null $enforcedCenterId = null): ?ResourceRequest
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
        $data['status_id'] = $statusIds[ResourceRequestStatus::PENDING] ?? 1;
        $data['handled_by'] = null;

        $requestRecord = ResourceRequest::create($data);
        return $requestRecord->load($this->requestRelations());
    }

    public function updateStatus(string $id, string $statusKey, string $handlerUserId): ResourceRequest
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
