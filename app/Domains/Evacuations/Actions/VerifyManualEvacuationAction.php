<?php

namespace App\Domains\Evacuations\Actions;

use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Evacuations\DTOs\AdmissionDTO;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\Households\Models\HouseholdStatus;
use App\Exceptions\HouseholdAlreadyEvacuatedException;
use App\Exceptions\MembersAlreadyEvacuatedException;
use App\Exceptions\NoCenterAssignedException;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use Illuminate\Support\Facades\DB;

class VerifyManualEvacuationAction
{
    public function __construct(
        private EvacuationRepositoryInterface $evacuationRepository,
        private HouseholdRepositoryInterface $householdRepository
    ) {}

    public function execute(AdmissionDTO $dto): array
    {
        return DB::connection('mysql_v2')->transaction(function () use ($dto) {
            $center = EvacuationCenter::where('evacuation_center_id', $dto->centerId)->firstOrFail();
            if (!$center->current_event_id) {
                throw new NoCenterAssignedException(
                    'Cannot admit household: This evacuation center is not assigned to an active disaster event.'
                );
            }

            if ($this->evacuationRepository->isHouseholdEvacuatedAtCenter($dto->householdId, $dto->centerId)) {
                throw new HouseholdAlreadyEvacuatedException();
            }

            $household = $this->householdRepository->findWithRelations($dto->householdId);

            $admitMemberIds = !empty($dto->memberIds)
                ? $dto->memberIds
                : $household->members->pluck('member_id')->toArray();

            $registeredMemberCount = count($admitMemberIds);
            $declaredCount = $dto->memberCount ?: max(1, $household->members->count() ?: 1);
            $totalEvacuatedCount = max($registeredMemberCount, $declaredCount);
            
            $eventId = $this->evacuationRepository->resolveEventId($dto->eventId, $dto->centerId);

            if ($registeredMemberCount > 0) {
                $evacuatedElsewhere = $this->evacuationRepository->getEvacuatedCenterIdsForMembers($admitMemberIds);
                if (!empty($evacuatedElsewhere)) {
                    throw new MembersAlreadyEvacuatedException(
                        "Some members are already evacuated in center ID(s): " . implode(', ', $evacuatedElsewhere)
                    );
                }
            }

            $record = $this->evacuationRepository->createRecord([
                'household_id'        => $dto->householdId,
                'center_id'           => $dto->centerId,
                'user_id'             => $dto->userId,
                'event_id'            => $eventId,
                'evacuated_count'     => $totalEvacuatedCount,
                'verified_at'         => now(),
                'method'              => 'manual',
                'household_status_id' => HouseholdStatus::EVACUATED,
            ]);

            if ($registeredMemberCount > 0) {
                $this->evacuationRepository->createEvacuatedMembers($record, $admitMemberIds);
            }

            $remainingCount = $totalEvacuatedCount - $registeredMemberCount;
            if ($remainingCount > 0) {
                $this->evacuationRepository->createEvacuatedMembersWithCount($record, $remainingCount);
            }

            $currentCount = (int) ($household->member_count ?: 0);
            if ($totalEvacuatedCount > $currentCount) {
                $household->update(['member_count' => $totalEvacuatedCount]);
            }

            return [
                'evacuation' => $this->evacuationRepository->findById($record->evacuation_id),
                'household'  => $household->fresh(['members', 'address']),
            ];
        });
    }
}
