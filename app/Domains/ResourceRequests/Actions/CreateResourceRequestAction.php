<?php

namespace App\Domains\ResourceRequests\Actions;

use App\Domains\Authentication\Models\User;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Notifications\Models\UserNotification;
use App\Domains\ResourceRequests\DTOs\ResourceRequestDTO;
use App\Domains\ResourceRequests\Models\ResourceRequest;
use App\Domains\ResourceRequests\Repositories\ResourceRequestRepositoryInterface;
use Illuminate\Support\Facades\Log;

class CreateResourceRequestAction
{
    public function __construct(
        private ResourceRequestRepositoryInterface $repository
    ) {}

    public function execute(ResourceRequestDTO $dto, string $requesterUserId, int|string|null $enforcedCenterId = null): ResourceRequest
    {
        $data = $dto->toArray();
        $data['requested_by'] = $requesterUserId;
        
        if ($enforcedCenterId !== null) {
            $data['evacuation_center_id'] = $enforcedCenterId;
        }

        $requestRecord = $this->repository->createRequest($data);

        // Dispatch real-time in-app notifications to Administrators
        $this->notifyAdministrators($requestRecord, $requesterUserId);

        return $requestRecord;
    }

    /**
     * Dispatch in-app notifications (user_notifications) to all relevant administrators
     * when a new resource request is submitted.
     */
    protected function notifyAdministrators(ResourceRequest $requestRecord, string $requesterUserId): void
    {
        try {
            $requester = User::find($requesterUserId);
            $requesterName = $requester ? $requester->name : 'A personnel member';

            $center = $requestRecord->center ?: EvacuationCenter::find($requestRecord->evacuation_center_id);
            $centerName = $center ? $center->name : 'an evacuation center';

            $resourceType = $requestRecord->resource_type ?: 'supplies';
            $quantity = (int) ($requestRecord->quantity ?? 0);
            $quantityText = $quantity > 0 ? " ({$quantity} units)" : "";

            // Active Super Admins + Evacuation Admins associated with this center (or system-wide)
            $admins = User::query()
                ->where('is_active', true)
                ->where('user_id', '!=', $requesterUserId)
                ->where(function ($q) use ($requestRecord) {
                    $q->where('role_id', User::ROLE_SUPER_ADMIN_ID)
                      ->orWhere(function ($eq) use ($requestRecord) {
                          $eq->where('role_id', User::ROLE_EVAC_ADMIN_ID)
                             ->where(function ($cq) use ($requestRecord) {
                                 $cq->where('assigned_center_id', $requestRecord->evacuation_center_id)
                                    ->orWhereNull('assigned_center_id');
                             });
                      });
                })
                ->get();

            $title = 'New Resource Request Submitted';
            $message = "{$requesterName} submitted a new request for {$resourceType}{$quantityText} at {$centerName}.";
            $data = [
                'request_id'   => (string) $requestRecord->request_id,
                'center_id'    => (string) $requestRecord->evacuation_center_id,
                'center_name'  => $centerName,
                'resource_type'=> $resourceType,
                'quantity'     => $quantity,
                'requested_by' => $requesterName,
                'link'         => '/resource-requests',
            ];

            foreach ($admins as $admin) {
                UserNotification::create([
                    'user_id' => $admin->user_id,
                    'type'    => 'resource_request_created',
                    'title'   => $title,
                    'message' => $message,
                    'data'    => $data,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[CreateResourceRequestAction] Failed to dispatch admin notifications: ' . $e->getMessage(), [
                'request_id' => $requestRecord->request_id ?? null,
            ]);
        }
    }
}
