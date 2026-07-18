<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DeleteMemberAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository
    ) {}

    public function execute(string $memberId): void
    {
        DB::transaction(function () use ($memberId) {
            $this->repository->deleteMember($memberId);
        });
    }
}
