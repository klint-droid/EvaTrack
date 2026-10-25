<?php

namespace App\Domains\Households\Actions;

use App\Domains\Households\DTOs\HouseholdDTO;
use App\Domains\Households\Models\Household;
use App\Domains\Households\Models\HouseholdMember;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\ReferenceData\Models\Relationship;
use App\Domains\ReferenceData\Models\Gender;
use App\Domains\ReferenceData\Models\CivilStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateHouseholdAction
{
    public function __construct(
        private HouseholdRepositoryInterface $repository
    ) {}

    public function execute(HouseholdDTO $dto): Household
    {
        return DB::connection('mysql_v2')->transaction(function () use ($dto) {
            $household = $this->repository->create($dto);

            if ($dto->barangay || $dto->fullAddress) {
                $this->repository->createAddress($household, $dto);
            }

            if (!empty($dto->members)) {
                $count = 0;
                foreach ($dto->members as $idx => $m) {
                    $birthDate = !empty($m['birth_date']) ? Carbon::parse($m['birth_date'])->format('Y-m-d') : null;
                    $age = $birthDate ? Carbon::parse($birthDate)->age : null;
                    $isSenior = ($age !== null && $age >= 60) ? 1 : 0;
                    $isChild = ($age !== null && $age < 18) ? 1 : 0;

                    $genderId = !empty($m['gender_id']) ? (int) $m['gender_id'] : null;
                    $genderLabel = $m['gender'] ?? null;
                    if (!$genderLabel && $genderId) {
                        $genderLabel = Gender::find($genderId)?->gender_label;
                    }

                    $relId = !empty($m['relationship_id']) ? (int) $m['relationship_id'] : null;
                    $relLabel = $m['relation'] ?? null;
                    if (!$relLabel && $relId) {
                        $relLabel = Relationship::find($relId)?->relationship_label;
                    }

                    $civilId = !empty($m['civil_status_id']) ? (int) $m['civil_status_id'] : null;
                    $civilLabel = $m['civil_status'] ?? null;
                    if (!$civilLabel && $civilId) {
                        $civilLabel = CivilStatus::find($civilId)?->status_label;
                    }

                    $isHead = !empty($m['is_household_head']) || ($idx === 0 && empty($m['relationship_id'])) ? 1 : 0;
                    $fullName = trim(($m['first_name'] ?? '') . ' ' . ($m['middle_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));

                    $member = HouseholdMember::create([
                        'household_id'             => $household->household_id,
                        'first_name'               => $m['first_name'],
                        'middle_name'              => $m['middle_name'] ?? null,
                        'last_name'                => $m['last_name'],
                        'name'                     => $fullName,
                        'birth_date'               => $birthDate,
                        'gender_id'                => $genderId,
                        'gender'                   => $genderLabel,
                        'sex'                      => $m['sex'] ?? ($genderLabel ? strtoupper(substr($genderLabel, 0, 1)) : null),
                        'relationship_id'          => $relId,
                        'relation'                 => $relLabel,
                        'civil_status_id'          => $civilId,
                        'civil_status'             => $civilLabel,
                        'is_household_head'        => $isHead,
                        'can_report_for_household' => $isHead || !empty($m['can_report_for_household']) ? 1 : 0,
                        'is_senior'                => $isSenior,
                        'is_pwd'                   => !empty($m['is_pwd']) ? 1 : 0,
                        'is_pregnant'              => !empty($m['is_pregnant']) ? 1 : 0,
                        'contact_number'           => $m['contact_number'] ?? $household->contact_number,
                    ]);

                    // Vulnerable groups sync
                    $vulnerableIds = array_map('intval', $m['vulnerable_group_ids'] ?? []);
                    if ($isSenior && !in_array(1, $vulnerableIds)) $vulnerableIds[] = 1;
                    if ($isChild && !in_array(2, $vulnerableIds)) $vulnerableIds[] = 2;
                    if (!empty($m['is_pwd']) && !in_array(3, $vulnerableIds)) $vulnerableIds[] = 3;
                    if (!empty($m['is_pregnant']) && !in_array(4, $vulnerableIds)) $vulnerableIds[] = 4;

                    if (!empty($vulnerableIds)) {
                        $member->vulnerableGroupDetails()->sync($vulnerableIds);
                    }

                    $count++;
                }

                $household->update(['member_count' => $count]);
            }

            return $this->repository->findWithRelations($household->household_id);
        });
    }
}
