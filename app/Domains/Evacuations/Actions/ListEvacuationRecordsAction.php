<?php

namespace App\Domains\Evacuations\Actions;

use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Evacuations\DTOs\EvacuationFilterDTO;
use Illuminate\Database\Eloquent\Collection;

class ListEvacuationRecordsAction
{
    public function __construct(
        private EvacuationRepositoryInterface $evacuationRepository
    ) {}

    public function execute(EvacuationFilterDTO $filters): Collection
    {
        return $this->evacuationRepository->getFilteredList($filters);
    }
}
