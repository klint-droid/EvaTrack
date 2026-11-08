<?php

namespace App\Domains\Evacuations\Actions;

use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;
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
        private HouseholdRepositoryInterface $householdRepository,
        private ?AccommodationUnitRepositoryInterface $unitRepository = null
    ) {}

    public function execute(AdmissionDTO $dto): array
    {
        return DB::connection('mysql_v2')->transaction(function () use ($dto) {
            HouseholdStatus::ensureSeeded();
            $household = $this->householdRepository->findWithRelations($dto->householdId);

            if ($household->members->count() < 1) {
                throw new \Exception('This household has no registered members. Please add household members first.');
            }

            $admitMemberIds = !empty($dto->memberIds)
                ? $dto->memberIds
                : $household->members->filter(function ($m) {
                    return !$m->evacuatedMembers()->whereHas('evacuationRecord', function ($er) {
                        $er->where('household_status_id', HouseholdStatus::EVACUATED)
                           ->where(function ($q) {
                               $q->whereNull('event_id')
                                 ->orWhereHas('event', function ($eq) {
                                     $eq->whereNull('ended_at');
                                 });
                           });
                    })->exists();
                })->pluck('member_id')->toArray();

            if (empty($admitMemberIds)) {
                throw new HouseholdAlreadyEvacuatedException('All members of this household are already evacuated.');
            }

            $evacuatedCenterIds = $this->evacuationRepository->getEvacuatedCenterIdsForMembers($admitMemberIds);
            $evacuatedElsewhere = array_values(array_diff($evacuatedCenterIds, [$dto->centerId]));
            if (!empty($evacuatedElsewhere)) {
                throw new MembersAlreadyEvacuatedException(
                    "Some members are already evacuated in center ID(s): " . implode(', ', $evacuatedElsewhere)
                );
            }

            $eventId = $this->evacuationRepository->resolveEventId($dto->eventId, $dto->centerId);

            $existingRecord = \App\Domains\Evacuations\Models\EvacuationRecord::where('household_id', $dto->householdId)
                ->where('center_id', $dto->centerId)
                ->where('household_status_id', HouseholdStatus::EVACUATED)
                ->where(function ($q) {
                    $q->whereNull('event_id')
                      ->orWhereHas('event', function ($eq) {
                          $eq->whereNull('ended_at');
                      });
                })
                ->latest('verified_at')
                ->first();

            if ($existingRecord) {
                $this->evacuationRepository->createEvacuatedMembers($existingRecord, $admitMemberIds);
                $newTotal = $existingRecord->evacuatedMembers()->whereNotNull('member_id')->count();
                $existingRecord->update([
                    'evacuated_count' => $newTotal,
                    'event_id'        => $eventId ?? $existingRecord->event_id,
                    'verified_by'     => $dto->userId,
                    'verified_at'     => now(),
                ]);
                if (method_exists($this->evacuationRepository, 'syncCenterOccupancy')) {
                    $this->evacuationRepository->syncCenterOccupancy((string) $dto->centerId);
                }
                $record = $existingRecord;
            } else {
                $record = $this->evacuationRepository->createRecord([
                    'household_id'        => $dto->householdId,
                    'center_id'           => $dto->centerId,
                    'verified_by'         => $dto->userId,
                    'event_id'            => $eventId,
                    'evacuated_count'     => count($admitMemberIds),
                    'verified_at'         => now(),
                    'method'              => 'qr',
                    'household_status_id' => HouseholdStatus::EVACUATED,
                ]);

                $this->evacuationRepository->createEvacuatedMembers($record, $admitMemberIds);
            }

            if ($dto->unitId) {
                $unitRepo = $this->unitRepository ?? app(AccommodationUnitRepositoryInterface::class);
                $unitRepo->assignHousehold($dto->unitId, $record->evacuation_id, $dto->userId);
            }

            return [
                'record'    => $this->evacuationRepository->findById($record->evacuation_id),
                'household' => $this->householdRepository->findWithRelations($dto->householdId),
            ];
        });
    }
}
