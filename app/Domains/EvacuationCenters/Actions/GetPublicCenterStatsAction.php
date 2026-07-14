<?php

namespace App\Domains\EvacuationCenters\Actions;

use App\Domains\EvacuationCenters\Repositories\EvacuationCenterRepositoryInterface;

class GetPublicCenterStatsAction
{
    public function __construct(
        private EvacuationCenterRepositoryInterface $repository
    ) {}

    public function execute(): array
    {
        $centers = $this->repository->getAllWithOccupancy();

        $totalCenters = $centers->count();
        $totalEvacuees = (int) $centers->sum('current_occupancy');
        $totalCapacity = (int) $centers->sum('capacity');
        $avgCapacity = $totalCapacity > 0 ? (int) round(($totalEvacuees / $totalCapacity) * 100) : 0;

        $fullCenters = $centers->filter(function ($center) {
            return $center->capacity > 0 && $center->current_occupancy >= $center->capacity;
        })->count();

        return [
            'centers' => $centers,
            'stats' => [
                'total_centers'  => $totalCenters,
                'total_evacuees' => $totalEvacuees,
                'avg_capacity'   => $avgCapacity,
                'full_centers'   => $fullCenters,
            ]
        ];
    }
}
