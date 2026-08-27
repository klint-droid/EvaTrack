<?php

namespace App\Domains\Households\Repositories;

use App\Domains\Households\DTOs\HouseholdDTO;
use App\Domains\Households\DTOs\HouseholdFilterDTO;
use App\Domains\Households\DTOs\MemberDTO;
use App\Domains\Households\Models\Household;
use App\Domains\Households\Models\HouseholdMember;
use App\Domains\Households\Models\HouseholdStatus;
use App\Domains\ReferenceData\Models\Address;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentHouseholdRepository implements HouseholdRepositoryInterface
{
    private array $relations = [
        'members',
        'members.gender',
        'members.relationship',
        'members.civilStatus',
        'members.vulnerableGroupDetails',
        'members.evacuatedMembers.evacuationRecord.center',
        'members.evacuatedMembers.evacuationRecord.event',
        'address',
        'currentEvacuation.center',
        'currentEvacuation.event',
        'currentEvacuation.unitAllocation.unit.type', 
        'currentEvacuation.verifier',                 
        'currentEvacuation.evacuatedMembers',
        'currentEvacuations.center',
        'currentEvacuations.event',
        'currentEvacuations.unitAllocation.unit.type',
        'currentEvacuations.evacuatedMembers',
    ];

    public function findWithRelations(string $id): Household
    {
        return Household::with($this->relations)
            ->where('household_id', $id)
            ->firstOrFail();
    }

    public function getFilteredList(HouseholdFilterDTO $filters, ?string $assignedCenterId): LengthAwarePaginator
    {
        $query = Household::withCount('members')->with([
            'address',
            'currentEvacuation.center',
            'currentEvacuation.event',
            'currentEvacuation.unitAllocation.unit',
        ]);

        $targetCenterId = $filters->centerId ?? ($assignedCenterId && $filters->status === 'evacuated' ? $assignedCenterId : null);

        if ($targetCenterId) {
            $query->whereHas('currentEvacuation', fn($q) => $q->where('center_id', $targetCenterId));
        }

        $cleanSearch = trim($filters->search ?? '');
        if (!empty($cleanSearch)) {
            $tokens = array_values(array_filter(explode(' ', $cleanSearch)));
            $query->where(function ($builder) use ($cleanSearch, $tokens) {
                $builder->where('household_name', 'LIKE', "%{$cleanSearch}%")
                    ->orWhere('household_id', 'LIKE', "%{$cleanSearch}%")
                    ->orWhere('contact_number', 'LIKE', "%{$cleanSearch}%")
                    ->orWhereHas('members', function ($q) use ($cleanSearch) {
                        $q->where('first_name', 'LIKE', "%{$cleanSearch}%")
                            ->orWhere('last_name', 'LIKE', "%{$cleanSearch}%")
                            ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$cleanSearch}%")
                            ->orWhere(DB::raw("CONCAT(last_name, ' ', first_name)"), 'LIKE', "%{$cleanSearch}%")
                            ->orWhere(DB::raw("CONCAT_WS(' ', first_name, middle_name, last_name)"), 'LIKE', "%{$cleanSearch}%");
                    });

                if (count($tokens) > 1) {
                    $builder->orWhere(function ($subBuilder) use ($tokens) {
                        foreach ($tokens as $token) {
                            $subBuilder->where(function ($tokenBuilder) use ($token) {
                                $tokenBuilder->where('household_name', 'LIKE', "%{$token}%")
                                    ->orWhere('household_id', 'LIKE', "%{$token}%")
                                    ->orWhere('contact_number', 'LIKE', "%{$token}%")
                                    ->orWhereHas('members', function ($q) use ($token) {
                                        $q->where('first_name', 'LIKE', "%{$token}%")
                                            ->orWhere('last_name', 'LIKE', "%{$token}%")
                                            ->orWhere('middle_name', 'LIKE', "%{$token}%");
                                    });
                            });
                        }
                    });
                }
            });
        }

        if (!empty($filters->eventId)) {
            if ($filters->status === 'evacuated') {
                $query->whereHas('evacuations', function ($q) use ($filters) {
                    $q->where('event_id', $filters->eventId);
                });
            } else {
                $query->whereDoesntHave('evacuations', function ($q) use ($filters) {
                    $q->where('event_id', $filters->eventId);
                });
            }
        } else {
            if ($filters->status === 'evacuated') {
                $query->whereHas('currentEvacuation');
            } elseif ($filters->status === 'not_evacuated') {
                $query->whereDoesntHave('currentEvacuation');
            }
        }

        return $query->paginate(15, ['*'], 'page', $filters->page);
    }

    public function create(HouseholdDTO $dto): Household
    {
        return Household::create([
            'household_name' => $dto->householdName,
            'contact_number' => $dto->contactNumber,
            'address_id'     => $dto->addressId,
            'member_count'   => $dto->memberCount ?? 0,
        ]);
    }

    public function update(string $id, HouseholdDTO $dto): Household
    {
        $household = Household::with('address')->where('household_id', $id)->firstOrFail();

        $household->update(array_filter([
            'household_name' => $dto->householdName,
            'contact_number' => $dto->contactNumber,
        ]));

        if ($household->address) {
            $household->address->update(array_filter([
                'barangay'     => $dto->barangay,
                'street'       => $dto->street,
                'purok'        => $dto->purok,
                'city'         => $dto->city,
                'province'     => $dto->province,
                'full_address' => $dto->fullAddress,
            ]));
        }

        return $household;
    }

    public function delete(string $id): void
    {
        $household = Household::where('household_id', $id)->firstOrFail();
        
        if ($household->address) {
            $household->address->delete();
        }

        $household->members()->delete();
        $household->delete();
    }

    public function createAddress(Household $household, HouseholdDTO $dto): void
    {
        $address = Address::create([
            'barangay'     => $dto->barangay,
            'street'       => $dto->street,
            'purok'        => $dto->purok,
            'city'         => $dto->city,
            'province'     => $dto->province,
            'full_address' => $dto->fullAddress,
        ]);

        $household->update(['address_id' => $address->address_id]);
    }

    public function addMember(string $householdId, MemberDTO $dto): HouseholdMember
    {
        $member = HouseholdMember::create([
            'household_id'    => $householdId,
            'first_name'      => $dto->firstName,
            'middle_name'     => $dto->middleName,
            'last_name'       => $dto->lastName,
            'birth_date'      => $dto->birthDate,
            'gender_id'       => $dto->genderId,
            'relationship_id' => $dto->relationshipId,
            'civil_status_id' => $dto->civilStatusId,
        ]);

        if (!empty($dto->vulnerableGroupIds)) {
            $member->vulnerableGroupDetails()->sync($dto->vulnerableGroupIds);
        }

        return $member;
    }

    public function updateMember(string $memberId, MemberDTO $dto): HouseholdMember
    {
        $member = HouseholdMember::findOrFail($memberId);

        $member->update(array_filter([
            'first_name'      => $dto->firstName,
            'middle_name'     => $dto->middleName,
            'last_name'       => $dto->lastName,
            'birth_date'      => $dto->birthDate,
            'gender_id'       => $dto->genderId,
            'relationship_id' => $dto->relationshipId,
            'civil_status_id' => $dto->civilStatusId,
        ]));

        if ($dto->vulnerableGroupIds !== null) {
            $member->vulnerableGroupDetails()->sync($dto->vulnerableGroupIds);
        }

        return $member;
    }

    public function deleteMember(string $memberId): void
    {
        $member = HouseholdMember::findOrFail($memberId);
        $member->delete();
    }
}
