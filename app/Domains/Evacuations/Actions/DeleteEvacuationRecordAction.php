<?php

namespace App\Domains\Evacuations\Actions;

use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DeleteEvacuationRecordAction
{
    public function __construct(
        private EvacuationRepositoryInterface $evacuationRepository
    ) {}

    public function execute(int $evacuationId, int $centerId): void
    {
        DB::connection('mysql_v2')->transaction(function () use ($evacuationId, $centerId) {
            // Ensure record belongs to center
            $this->evacuationRepository->findById($evacuationId, $centerId);
            
            $this->evacuationRepository->deleteRecord($evacuationId);
        });
    }
}
