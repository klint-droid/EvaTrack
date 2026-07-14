<?php

namespace App\Domains\EvacuationCenters\Repositories;

use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use Illuminate\Database\Eloquent\Collection;

interface EvacuationCenterRepositoryInterface
{
    public function getAllWithOccupancy(): Collection;
    public function create(array $data): EvacuationCenter;
    public function update(EvacuationCenter $center, array $data): EvacuationCenter;
    public function delete(EvacuationCenter $center): void;
    public function getCapacityInfo(EvacuationCenter $center): array;
    public function clearCache(): void;
}
