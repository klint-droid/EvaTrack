<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\DTOs\HouseholdDTO;
use App\Domains\Households\Models\Household;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateHouseholdAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository
    ) {}

    public function execute(HouseholdDTO $dto): Household
    {
        return DB::transaction(function () use ($dto) {
            $household = $this->repository->create($dto);

            if ($dto->barangay || $dto->fullAddress) {
                $this->repository->createAddress($household, $dto);
            }

            // Note: We'd normally trigger HouseholdCreatedEvent here
            
            return $this->repository->findWithRelations($household->household_id);
        });
    }
}
