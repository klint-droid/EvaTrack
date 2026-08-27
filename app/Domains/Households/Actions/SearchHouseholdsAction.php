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
        $cleanQuery = trim($query);
        if (empty($cleanQuery)) {
            return [];
        }

        $tokens = array_values(array_filter(explode(' ', $cleanQuery)));

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
        ->where(function ($builder) use ($cleanQuery, $tokens) {
            // Direct phrase match on household attributes
            $builder->where('household_name', 'LIKE', "%{$cleanQuery}%")
                ->orWhere('household_id', 'LIKE', "%{$cleanQuery}%")
                ->orWhere('contact_number', 'LIKE', "%{$cleanQuery}%")
                ->orWhereHas('members', function ($q) use ($cleanQuery) {
                    $q->where('first_name', 'LIKE', "%{$cleanQuery}%")
                      ->orWhere('last_name', 'LIKE', "%{$cleanQuery}%")
                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$cleanQuery}%")
                      ->orWhere(DB::raw("CONCAT(last_name, ' ', first_name)"), 'LIKE', "%{$cleanQuery}%")
                      ->orWhere(DB::raw("CONCAT_WS(' ', first_name, middle_name, last_name)"), 'LIKE', "%{$cleanQuery}%");
                });

            // If multi-word search, all tokens must match
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
        })
        ->limit(15)
        ->get();
    }
}
