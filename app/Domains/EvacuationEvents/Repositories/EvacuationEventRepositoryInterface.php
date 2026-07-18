<?php

namespace App\Domains\EvacuationEvents\Repositories;

use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\EvacuationEvents\DTOs\EventFilterDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface EvacuationEventRepositoryInterface
{
    public function getAllEvents(): Collection;
    public function getHistoricalEvents(EventFilterDTO $filter): LengthAwarePaginator;
    public function getActiveEvent(): ?DisasterEvent;
    public function getPublicActiveEvents(): Collection;
    public function create(array $data): DisasterEvent;
    public function endEvent(DisasterEvent $event): DisasterEvent;
    public function assignCenters(DisasterEvent $event, array $centerIds): void;
    public function unassignCenter(string $centerId): void;
}
