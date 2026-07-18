<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\DTOs\MemberDTO;
use App\Domains\Households\Models\HouseholdMember;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UpdateMemberAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository
    ) {}

    public function execute(string $memberId, MemberDTO $dto): HouseholdMember
    {
        return DB::transaction(function () use ($memberId, $dto) {
            return $this->repository->updateMember($memberId, $dto);
        });
    }
}
