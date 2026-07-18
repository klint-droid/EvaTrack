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
        $cacheKey = "households_list_c{$assignedCenterId}_p{$filters->page}_q" . md5($filters->search) . "_s{$filters->status}_ci{$filters->centerId}_ev{$filters->eventId}";

        return Cache::tags(['households'])->remember($cacheKey, 300, function () use ($filters, $assignedCenterId) {
            $query = Household::withCount('members')->with([
                'address',
                'currentEvacuation.center',
                'currentEvacuation.event',
                'currentEvacuation.unitAllocation.unit',
            ]);

            if ($assignedCenterId) {
                $query->whereHas('currentEvacuation', fn($q) => $q->where('center_id', $assignedCenterId));
            }

            if (!empty($filters->search)) {
                $query->where(function ($builder) use ($filters) {
                    $builder->where('household_name', 'LIKE', "%{$filters->search}%")
                        ->orWhere('household_id', 'LIKE', "%{$filters->search}%")
                        ->orWhere('contact_number', 'LIKE', "%{$filters->search}%")
                        ->orWhereHas('members', function ($q) use ($filters) {
                            $q->where('first_name', 'LIKE', "%{$filters->search}%")
                                ->orWhere('last_name', 'LIKE', "%{$filters->search}%")
                                ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$filters->search}%");
                        });
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

            if (!empty($filters->centerId)) {
                $query->whereHas('currentEvacuation', function ($q) use ($filters) {
                    $q->where('center_id', $filters->centerId);
                });
            }

            return $query->paginate(15, ['*'], 'page', $filters->page);
        });
    }

    public function create(HouseholdDTO $dto): Household
    {
        return Household::create([
            'household_name' => $dto->householdName,
            'contact_number' => $dto->contactNumber,
            'address_id'     => $dto->addressId,
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

        Cache::tags(['households'])->flush();

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
        
        Cache::tags(['households'])->flush();
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

        Cache::tags(['households'])->flush();

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

        Cache::tags(['households'])->flush();

        return $member;
    }

    public function deleteMember(string $memberId): void
    {
        $member = HouseholdMember::findOrFail($memberId);
        $member->delete();
        Cache::tags(['households'])->flush();
    }
}
