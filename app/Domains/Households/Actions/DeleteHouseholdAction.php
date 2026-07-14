<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DeleteHouseholdAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository
    ) {}

    public function execute(string $id): void
    {
        DB::transaction(function () use ($id) {
            $this->repository->delete($id);
        });
    }
}
