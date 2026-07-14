<?php

namespace App\Domains\Households\Repositories;

use App\Domains\Households\DTOs\HouseholdDTO;
use App\Domains\Households\DTOs\HouseholdFilterDTO;
use App\Domains\Households\DTOs\MemberDTO;
use App\Domains\Households\Models\Household;
use App\Domains\Households\Models\HouseholdMember;
use Illuminate\Pagination\LengthAwarePaginator;

interface HouseholdRepositoryInterface
{
    public function findWithRelations(string $id): Household;
    public function getFilteredList(HouseholdFilterDTO $filters, ?int $assignedCenterId): LengthAwarePaginator;
    public function create(HouseholdDTO $dto): Household;
    public function update(string $id, HouseholdDTO $dto): Household;
    public function delete(string $id): void;
    public function createAddress(Household $household, HouseholdDTO $dto): void;
    
    // Member operations
    public function addMember(string $householdId, MemberDTO $dto): HouseholdMember;
    public function updateMember(int $memberId, MemberDTO $dto): HouseholdMember;
    public function deleteMember(int $memberId): void;
}
