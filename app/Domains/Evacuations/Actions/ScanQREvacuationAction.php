<?php

namespace App\Domains\Evacuations\Actions;

use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Evacuations\DTOs\AdmissionDTO;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\Households\Models\HouseholdStatus;
use App\Exceptions\HouseholdAlreadyEvacuatedException;
use App\Exceptions\MembersAlreadyEvacuatedException;
use Illuminate\Support\Facades\DB;

class ScanQREvacuationAction
{
    public function __construct(
        private EvacuationRepositoryInterface $evacuationRepository,
        private HouseholdRepositoryInterface $householdRepository
    ) {}

    public function execute(AdmissionDTO $dto): array
    {
        return DB::connection('mysql_v2')->transaction(function () use ($dto) {
            $household = $this->householdRepository->findWithRelations($dto->householdId);

            if ($household->members->count() < 1) {
                throw new \Exception('This household has no registered members. Please add household members first.');
            }

            $admitMemberIds = !empty($dto->memberIds)
                ? $dto->memberIds
                : $household->members->filter(function ($m) {
                    return !$m->evacuatedMembers()->whereHas('evacuationRecord', function ($er) {
                        $er->where('household_status_id', HouseholdStatus::EVACUATED);
                    })->exists();
                })->pluck('member_id')->toArray();

            if (empty($admitMemberIds)) {
                throw new HouseholdAlreadyEvacuatedException('All members of this household are already evacuated.');
            }

            $evacuatedElsewhere = $this->evacuationRepository->getEvacuatedCenterIdsForMembers($admitMemberIds);
            if (!empty($evacuatedElsewhere)) {
                throw new MembersAlreadyEvacuatedException(
                    "Some members are already evacuated in center ID(s): " . implode(', ', $evacuatedElsewhere)
                );
            }

            $eventId = $this->evacuationRepository->resolveEventId($dto->eventId, $dto->centerId);

            $existingRecord = \App\Domains\Evacuations\Models\EvacuationRecord::where('household_id', $dto->householdId)
                ->where('center_id', $dto->centerId)
                ->where('household_status_id', HouseholdStatus::EVACUATED)
                ->first();

            if ($existingRecord) {
                $this->evacuationRepository->createEvacuatedMembers($existingRecord, $admitMemberIds);
                $newTotal = $existingRecord->evacuatedMembers()->whereNotNull('member_id')->count();
                $existingRecord->update(['evacuated_count' => $newTotal]);
                $record = $existingRecord;
            } else {
                $record = $this->evacuationRepository->createRecord([
                    'household_id'        => $dto->householdId,
                    'center_id'           => $dto->centerId,
                    'user_id'             => $dto->userId,
                    'event_id'            => $eventId,
                    'evacuated_count'     => count($admitMemberIds),
                    'verified_at'         => now(),
                    'method'              => 'qr',
                    'household_status_id' => HouseholdStatus::EVACUATED,
                ]);

                $this->evacuationRepository->createEvacuatedMembers($record, $admitMemberIds);
            }

            return [
                'record'    => $this->evacuationRepository->findById($record->evacuation_id),
                'household' => $household->fresh(['members', 'address']),
            ];
        });
    }
}
