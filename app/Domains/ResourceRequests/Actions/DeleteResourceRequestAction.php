<?php

namespace App\Domains\ResourceRequests\Actions;

use App\Domains\ResourceRequests\Repositories\ResourceRequestRepositoryInterface;
use App\Domains\ResourceRequests\Models\ResourceRequestStatus;
use App\Domains\Authentication\Models\User;
use Exception;

class DeleteResourceRequestAction
{
    public function __construct(
        private ResourceRequestRepositoryInterface $repository
    ) {}

    public function execute(string $id, User $user, ?int $enforcedCenterId = null): void
    {
        $request = $this->repository->getRequestById($id, $enforcedCenterId);
        
        if (!$request) {
            throw new Exception("Request not found or unauthorized.", 404);
        }

        $pendingStatusId = ResourceRequestStatus::where('status_key', ResourceRequestStatus::PENDING)->value('status_id');

        if ($user->isEvacPersonnel()) {
            if ($request->requested_by !== $user->user_id) {
                throw new Exception("You can only delete your own request.", 403);
            }

            if ($request->status_id !== $pendingStatusId) {
                throw new Exception("Only pending requests can be deleted.", 400);
            }
        }

        $this->repository->deleteRequest($request);
    }
}
