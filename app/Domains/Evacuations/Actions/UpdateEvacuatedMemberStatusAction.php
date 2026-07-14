<?php

namespace App\Domains\Evacuations\Actions;

use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Evacuations\Models\EvacuatedMember;
use Illuminate\Support\Facades\DB;

class UpdateEvacuatedMemberStatusAction
{
    public function __construct(
        private EvacuationRepositoryInterface $evacuationRepository
    ) {}

    public function execute(int $evacuationId, int $memberId, string $status, int $centerId): EvacuatedMember
    {
        return DB::connection('mysql_v2')->transaction(function () use ($evacuationId, $memberId, $status, $centerId) {
            // Verify ownership
            $this->evacuationRepository->findById($evacuationId, $centerId);

            if (!in_array($status, ['Inside Center', 'Checked Out', 'Transferred'])) {
                throw new \InvalidArgumentException('Invalid member status.');
            }

            return $this->evacuationRepository->updateEvacuatedMember($evacuationId, $memberId, [
                'status' => $status
            ]);
        });
    }
}
