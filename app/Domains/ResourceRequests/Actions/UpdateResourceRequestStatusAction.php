<?php

namespace App\Domains\ResourceRequests\Actions;

use App\Domains\Authentication\Models\User;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Notifications\Models\UserNotification;
use App\Domains\ResourceRequests\Models\ResourceRequest;
use App\Domains\ResourceRequests\Repositories\ResourceRequestRepositoryInterface;
use Illuminate\Support\Facades\Log;

class UpdateResourceRequestStatusAction
{
    public function __construct(
        private ResourceRequestRepositoryInterface $repository
    ) {}

    public function execute(string $id, string $statusKey, string $handlerUserId): ResourceRequest
    {
        $updated = $this->repository->updateStatus($id, $statusKey, $handlerUserId);

        // Dispatch real-time in-app notification to the requesting user
        $this->notifyRequester($updated, $statusKey, $handlerUserId);

        return $updated;
    }

    /**
     * Dispatch in-app notification (user_notifications) to the requesting staff member
     * when their request status changes.
     */
    protected function notifyRequester(ResourceRequest $requestRecord, string $statusKey, string $handlerUserId): void
    {
        try {
            $requesterId = $requestRecord->requested_by;
            if (empty($requesterId)) {
                return;
            }

            // Avoid self-notification if handler is the requester
            if ($requesterId === $handlerUserId) {
                return;
            }

            $handler = User::find($handlerUserId);
            $handlerName = $handler ? $handler->name : 'An administrator';

            $statusModel = $requestRecord->status;
            $statusLabel = $statusModel ? $statusModel->status_label : ucfirst(str_replace('_', ' ', $statusKey));

            $resourceType = $requestRecord->resource_type ?: 'supplies';
            $center = $requestRecord->center ?: EvacuationCenter::find($requestRecord->evacuation_center_id);
            $centerName = $center ? $center->name : 'your evacuation center';

            $title = "Resource Request {$statusLabel}";
            $message = "Your resource request for {$resourceType} at {$centerName} has been marked as {$statusLabel} by {$handlerName}.";

            $data = [
                'request_id'   => (string) $requestRecord->request_id,
                'center_id'    => (string) $requestRecord->evacuation_center_id,
                'center_name'  => $centerName,
                'resource_type'=> $resourceType,
                'status'       => $statusKey,
                'status_label' => $statusLabel,
                'handled_by'   => $handlerName,
                'link'         => '/resource-requests',
            ];

            UserNotification::create([
                'user_id' => $requesterId,
                'type'    => 'resource_request_status',
                'title'   => $title,
                'message' => $message,
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('[UpdateResourceRequestStatusAction] Failed to dispatch requester notification: ' . $e->getMessage(), [
                'request_id' => $requestRecord->request_id ?? null,
                'status'     => $statusKey,
            ]);
        }
    }
}
