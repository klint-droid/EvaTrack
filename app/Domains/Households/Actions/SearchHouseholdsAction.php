<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\Households\Models\Household;
use Illuminate\Support\Facades\DB;

class SearchHouseholdsAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository
    ) {}

    public function execute(string $query): mixed
    {
        return Household::with([
            'address',
            'members',
            'members.gender',
            'members.relationship',
            'members.civilStatus',
            'members.vulnerableGroupDetails',
            'members.evacuatedMembers.evacuationRecord.center',
            'members.evacuatedMembers.evacuationRecord.event',
            'currentEvacuation.center',
            'currentEvacuation.event',
            'currentEvacuation.unitAllocation.unit',
            'currentEvacuation.evacuatedMembers',
            'currentEvacuations.center',
            'currentEvacuations.event',
            'currentEvacuations.unitAllocation.unit',
            'currentEvacuations.evacuatedMembers',
        ])
        ->withCount('members')
        ->where(function ($builder) use ($query) {
            $builder->where('household_name', 'LIKE', "%{$query}%")
                ->orWhere('household_id', 'LIKE', "%{$query}%")
                ->orWhere('contact_number', 'LIKE', "%{$query}%")
                ->orWhereHas('members', function ($q) use ($query) {
                    $q->where('first_name', 'LIKE', "%{$query}%")
                      ->orWhere('last_name', 'LIKE', "%{$query}%")
                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$query}%");
                });
        })
        ->limit(15)
        ->get();
    }
}
