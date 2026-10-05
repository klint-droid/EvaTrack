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

    public function execute(string $query, string $type = 'household'): mixed
    {
        $cleanQuery = trim($query);
        if (mb_strlen($cleanQuery) < 1) {
            return [];
        }

        $tokens = array_values(array_filter(explode(' ', $cleanQuery)));
        $isIdPattern = preg_match('/[0-9\-]/', $cleanQuery) || preg_match('/^(mamb|nhh|hh)/i', $cleanQuery);

        $builder = Household::with([
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
            'latestEvacuation.center',
            'latestEvacuation.event',
        ])
        ->withCount('members');

        if ($type === 'member') {
            // Strictly search individual household member names
            $builder->whereHas('members', function ($q) use ($cleanQuery, $tokens) {
                $q->where(function ($sub) use ($cleanQuery, $tokens) {
                    $sub->where('first_name', 'LIKE', "{$cleanQuery}%")
                        ->orWhere('last_name', 'LIKE', "{$cleanQuery}%")
                        ->orWhere('first_name', 'LIKE', "% {$cleanQuery}%")
                        ->orWhere('last_name', 'LIKE', "% {$cleanQuery}%")
                        ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "{$cleanQuery}%")
                        ->orWhere(DB::raw("CONCAT(last_name, ' ', first_name)"), 'LIKE', "{$cleanQuery}%")
                        ->orWhere(DB::raw("CONCAT_WS(' ', first_name, middle_name, last_name)"), 'LIKE', "%{$cleanQuery}%");

                    if (mb_strlen($cleanQuery) >= 3) {
                        $sub->orWhere('first_name', 'LIKE', "%{$cleanQuery}%")
                            ->orWhere('last_name', 'LIKE', "%{$cleanQuery}%")
                            ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$cleanQuery}%");
                    }

                    if (count($tokens) > 1) {
                        foreach ($tokens as $token) {
                            $sub->orWhere('first_name', 'LIKE', "{$token}%")
                                ->orWhere('last_name', 'LIKE', "{$token}%");
                        }
                    }
                });
            });
        } elseif ($type === 'all') {
            // Both household and member search with household name prioritization
            $builder->where(function ($b) use ($cleanQuery, $tokens, $isIdPattern) {
                $b->where('household_name', 'LIKE', "{$cleanQuery}%")
                    ->orWhere('household_name', 'LIKE', "% {$cleanQuery}%");

                if (mb_strlen($cleanQuery) >= 3) {
                    $b->orWhere('household_name', 'LIKE', "%{$cleanQuery}%");
                }

                if ($isIdPattern) {
                    $b->orWhere('household_id', 'LIKE', "{$cleanQuery}%")
                        ->orWhere('household_id', 'LIKE', "%{$cleanQuery}%");
                }

                $b->orWhere('contact_number', 'LIKE', "{$cleanQuery}%");

                $b->orWhereHas('members', function ($q) use ($cleanQuery) {
                    $q->where('first_name', 'LIKE', "{$cleanQuery}%")
                      ->orWhere('last_name', 'LIKE', "{$cleanQuery}%")
                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "{$cleanQuery}%")
                      ->orWhere(DB::raw("CONCAT(last_name, ' ', first_name)"), 'LIKE', "{$cleanQuery}%");
                });
            })
            ->orderByRaw("CASE WHEN household_name LIKE '{$cleanQuery}%' THEN 1 WHEN household_name LIKE '%{$cleanQuery}%' THEN 2 ELSE 3 END");
        } else {
            // Default: 'household' -> ONLY search Household Name, Household ID, Contact Number, and Head of Household
            $builder->where(function ($b) use ($cleanQuery, $tokens, $isIdPattern) {
                $b->where('household_name', 'LIKE', "{$cleanQuery}%")
                    ->orWhere('household_name', 'LIKE', "% {$cleanQuery}%");

                if (mb_strlen($cleanQuery) >= 3) {
                    $b->orWhere('household_name', 'LIKE', "%{$cleanQuery}%");
                }

                if ($isIdPattern) {
                    $b->orWhere('household_id', 'LIKE', "{$cleanQuery}%")
                        ->orWhere('household_id', 'LIKE', "%{$cleanQuery}%");
                }

                $b->orWhere('contact_number', 'LIKE', "{$cleanQuery}%");

                // Also match head of household if registered as head
                $b->orWhereHas('members', function ($q) use ($cleanQuery) {
                    $q->where('is_household_head', 1)
                      ->where(function ($headQ) use ($cleanQuery) {
                          $headQ->where('first_name', 'LIKE', "{$cleanQuery}%")
                                ->orWhere('last_name', 'LIKE', "{$cleanQuery}%")
                                ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "{$cleanQuery}%");
                      });
                });
            })
            ->orderByRaw("CASE WHEN household_name LIKE '{$cleanQuery}%' THEN 1 WHEN household_name LIKE '%{$cleanQuery}%' THEN 2 ELSE 3 END");
        }

        return $builder->limit(20)->get();
    }
}
