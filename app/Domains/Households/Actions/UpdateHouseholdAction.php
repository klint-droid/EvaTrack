<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\DTOs\HouseholdDTO;
use App\Domains\Households\Models\Household;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UpdateHouseholdAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository
    ) {}

    public function execute(string $id, HouseholdDTO $dto): Household
    {
        return DB::transaction(function () use ($id, $dto) {
            $this->repository->update($id, $dto);
            
            // Re-fetch to get updated relationships
            return $this->repository->findWithRelations($id);
        });
    }
}
