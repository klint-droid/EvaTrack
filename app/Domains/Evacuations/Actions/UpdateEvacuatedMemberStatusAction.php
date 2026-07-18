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

    public function execute(int $evacuationId, string $memberId, string $status, string $centerId): EvacuatedMember
    {
        return DB::connection('mysql_v2')->transaction(function () use ($evacuationId, $memberId, $status, $centerId) {
            // Verify ownership
            $evacRecord = $this->evacuationRepository->findById($evacuationId, $centerId);

            if (!in_array($status, ['Inside Center', 'Checked Out', 'Transferred', 'evacuated', 'not_verified'])) {
                throw new \InvalidArgumentException('Invalid member status.');
            }

            if (in_array($status, ['Inside Center', 'evacuated'])) {
                // Check in: create if not exists
                $evacMember = EvacuatedMember::where('evacuation_id', $evacuationId)
                    ->where('member_id', $memberId)
                    ->first();
                if (!$evacMember) {
                    $evacMember = EvacuatedMember::create([
                        'evacuation_id' => $evacuationId,
                        'member_id'     => $memberId,
                        'verified_at'   => now(),
                    ]);

                    // Consume one anonymous placeholder (null member_id) if exists
                    $placeholder = EvacuatedMember::where('evacuation_id', $evacuationId)
                        ->whereNull('member_id')
                        ->first();
                    if ($placeholder) {
                        $placeholder->delete();
                    }
                }
            } else {
                // Check out / Transfer: delete if exists
                $evacMember = EvacuatedMember::where('evacuation_id', $evacuationId)
                    ->where('member_id', $memberId)
                    ->first();
                if ($evacMember) {
                    $evacMember->delete();
                }

                $evacMember = $evacMember ?? new EvacuatedMember([
                    'evacuation_id' => $evacuationId,
                    'member_id'     => $memberId,
                ]);
            }

            // Recalculate count of evacuated members
            $newCount = EvacuatedMember::where('evacuation_id', $evacuationId)->count();
            $evacRecord->update(['evacuated_count' => $newCount]);

            return $evacMember;
        });
    }
}
