<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\DTOs\HouseholdFilterDTO;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\Households\Services\ExternalHouseholdService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ListHouseholdsAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository,
        private ExternalHouseholdService $externalHouseholdService
    ) {}

    public function execute(HouseholdFilterDTO $filters): LengthAwarePaginator
    {
        // Sync households from external partner system (with automatic cooldown)
        $this->externalHouseholdService->syncHouseholds($filters->forceSync ?? false);

        $user = Auth::user();
        
        $assignedCenterId = $user?->isEvacPersonnel() ? $user->assigned_center_id : null;

        return $this->repository->getFilteredList($filters, $assignedCenterId);
    }
}
