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
        if (mb_strlen($cleanQuery) < 2) {
            return [];
        }

        $tokens = array_values(array_filter(explode(' ', $cleanQuery)));
        $isIdPattern = preg_match('/[0-9\-]/', $cleanQuery) || preg_match('/^(mamb|nhh|hh)/i', $cleanQuery);

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
        ->where(function ($builder) use ($cleanQuery, $tokens, $isIdPattern) {
            // Household Name (prefix or word-start)
            $builder->where('household_name', 'LIKE', "{$cleanQuery}%")
                ->orWhere('household_name', 'LIKE', "% {$cleanQuery}%");

            if (mb_strlen($cleanQuery) >= 3) {
                $builder->orWhere('household_name', 'LIKE', "%{$cleanQuery}%");
            }

            // Household ID only if query looks like an ID / code (prevents 'a' from matching 'MAMB-')
            if ($isIdPattern) {
                $builder->orWhere('household_id', 'LIKE', "{$cleanQuery}%")
                    ->orWhere('household_id', 'LIKE', "%{$cleanQuery}%");
            }

            // Contact Number prefix
            $builder->orWhere('contact_number', 'LIKE', "{$cleanQuery}%");

            // Member Names (word start or full concat)
            $builder->orWhereHas('members', function ($q) use ($cleanQuery) {
                $q->where('first_name', 'LIKE', "{$cleanQuery}%")
                  ->orWhere('last_name', 'LIKE', "{$cleanQuery}%")
                  ->orWhere('first_name', 'LIKE', "% {$cleanQuery}%")
                  ->orWhere('last_name', 'LIKE', "% {$cleanQuery}%")
                  ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "{$cleanQuery}%")
                  ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "% {$cleanQuery}%")
                  ->orWhere(DB::raw("CONCAT(last_name, ' ', first_name)"), 'LIKE', "{$cleanQuery}%")
                  ->orWhere(DB::raw("CONCAT_WS(' ', first_name, middle_name, last_name)"), 'LIKE', "{$cleanQuery}%");

                if (mb_strlen($cleanQuery) >= 3) {
                    $q->orWhere('first_name', 'LIKE', "%{$cleanQuery}%")
                      ->orWhere('last_name', 'LIKE', "%{$cleanQuery}%")
                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$cleanQuery}%");
                }
            });

            // Multi-token exact match
            if (count($tokens) > 1) {
                $builder->orWhere(function ($subBuilder) use ($tokens) {
                    foreach ($tokens as $token) {
                        $subBuilder->where(function ($tokenBuilder) use ($token) {
                            $tokenBuilder->where('household_name', 'LIKE', "{$token}%")
                                ->orWhere('household_name', 'LIKE', "% {$token}%")
                                ->orWhere('contact_number', 'LIKE', "{$token}%")
                                ->orWhereHas('members', function ($q) use ($token) {
                                    $q->where('first_name', 'LIKE', "{$token}%")
                                      ->orWhere('last_name', 'LIKE', "{$token}%")
                                      ->orWhere('middle_name', 'LIKE', "{$token}%")
                                      ->orWhere('first_name', 'LIKE', "% {$token}%")
                                      ->orWhere('last_name', 'LIKE', "% {$token}%");
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
