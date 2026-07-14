<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\DTOs\HouseholdFilterDTO;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ListHouseholdsAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository
    ) {}

    public function execute(HouseholdFilterDTO $filters): LengthAwarePaginator
    {
        $user = Auth::user();
        
        $assignedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;

        return $this->repository->getFilteredList($filters, $assignedCenterId);
    }
}
