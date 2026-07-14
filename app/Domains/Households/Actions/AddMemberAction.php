<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\DTOs\MemberDTO;
use App\Domains\Households\Models\HouseholdMember;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AddMemberAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository
    ) {}

    public function execute(string $householdId, MemberDTO $dto): HouseholdMember
    {
        return DB::transaction(function () use ($householdId, $dto) {
            return $this->repository->addMember($householdId, $dto);
        });
    }
}
