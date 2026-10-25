<?php

namespace App\Domains\Households\Services;

use App\Domains\Households\Models\Household;
use App\Domains\Households\Models\HouseholdMember;
use App\Domains\ReferenceData\Models\Address;
use App\Domains\ReferenceData\Models\Barangay;
use App\Domains\ReferenceData\Models\Purok;
use App\Domains\ReferenceData\Models\Sitio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ExternalHouseholdService
{
    /**
     * Fetch households from the external system partner API and sync them into local database.
     *
     * @param bool $force Force sync even if within the cache lock window
     * @return array Status and summary of the sync operation
     */
    public function syncHouseholds(bool $force = false): array
    {
        // Prevent hammering the external host on rapid queries (e.g. pagination or quick search keystrokes)
        if (!$force && Cache::has('external_households_sync_lock')) {
            return [
                'status'  => 'skipped',
                'message' => 'Sync skipped due to active cooldown window.',
                'synced'  => 0,
            ];
        }

        $url = config('services.external_households.url')
            ?: rtrim(config('services.external_households.host', 'http://192.168.110.201:8000'), '/') . '/api/external/households';
        $apiKey = config('services.external_households.api_key', 'evatrack_live_sec_9a87f12bc34de56f');
        $timeout = (int) config('services.external_households.timeout', 5);

        if (empty($url)) {
            return [
                'status'  => 'skipped',
                'message' => 'No external households URL configured.',
                'synced'  => 0,
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Accept'    => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning("External households API error [{$response->status()}]: " . substr($response->body(), 0, 300));
                return [
                    'status'  => 'error',
                    'message' => "External API returned HTTP status {$response->status()}",
                    'synced'  => 0,
                ];
            }

            $body = $response->json();
            $data = $body['data'] ?? [];

            if (!is_array($data)) {
                return [
                    'status'  => 'skipped',
                    'message' => 'Invalid data payload received from external API',
                    'synced'  => 0,
                ];
            }

            $syncedCount = 0;

            foreach ($data as $item) {
                if ($this->upsertHouseholdItem($item)) {
                    $syncedCount++;
                }
            }

            // Lock sync for 45 seconds to prevent hammering the network
            Cache::put('external_households_sync_lock', true, 45);

            return [
                'status'  => 'success',
                'message' => "Successfully synced {$syncedCount} households from external partner system.",
                'synced'  => $syncedCount,
            ];
        } catch (\Throwable $e) {
            Log::warning('External households sync exception: ' . $e->getMessage());
            return [
                'status'  => 'error',
                'message' => 'Could not connect to external households API: ' . $e->getMessage(),
                'synced'  => 0,
            ];
        }
    }

    /**
     * Fetch a single household by ID from external partner system if not present locally.
     *
     * @param string $householdId
     * @return Household|null
     */
    public function syncSingleHousehold(string $householdId): ?Household
    {
        $existing = Household::where('household_id', $householdId)->first();
        if ($existing) {
            return $existing;
        }

        $url = config('services.external_households.url')
            ?: rtrim(config('services.external_households.host', 'http://192.168.110.201:8000'), '/') . '/api/external/households';
        $apiKey = config('services.external_households.api_key', 'evatrack_live_sec_9a87f12bc34de56f');
        $timeout = (int) config('services.external_households.timeout', 5);

        try {
            // Try query with search parameter
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Accept'    => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->get($url, ['search' => $householdId]);

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['data'] ?? [];
                if (is_array($data)) {
                    foreach ($data as $item) {
                        $id = $item['household_id'] ?? $item['household_code'] ?? null;
                        if ($id === $householdId) {
                            return $this->upsertHouseholdItem($item);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Single household sync exception for {$householdId}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Upsert a single household record with address and member relations.
     *
     * @param array $item
     * @return Household|null
     */
    public function upsertHouseholdItem(array $item): ?Household
    {
        $householdId = $item['household_id'] ?? $item['household_code'] ?? null;
        if (!$householdId) {
            return null;
        }

        // 1. Resolve or Upsert Address with full details
        $addressId = null;
        $existingHousehold = Household::where('household_id', $householdId)->first();
        if ($existingHousehold && $existingHousehold->address_id) {
            $addressId = $existingHousehold->address_id;
        }

        $location = $item['location'] ?? $item['address'] ?? [];
        if (is_string($location)) {
            $fullAddr = $location;
            $location = ['full_address' => $fullAddr];
        }

        $street = $location['street'] ?? $location['street_address'] ?? $item['street'] ?? null;
        $barangayName = $location['barangay_name'] ?? $location['barangay'] ?? $item['barangay'] ?? null;
        $sitioName = $location['sitio_name'] ?? $location['sitio'] ?? $location['purok_sitio'] ?? $item['sitio'] ?? null;
        $purokName = $location['purok_name'] ?? $location['purok'] ?? $item['purok'] ?? null;
        $cityName = $location['city_name'] ?? $location['city'] ?? $item['city'] ?? 'Cebu City';
        $provinceName = $location['province_name'] ?? $location['province'] ?? $item['province'] ?? 'Cebu';

        $barangayId = null;
        if ($barangayName) {
            $brgy = Barangay::where('barangay_name', 'LIKE', "%{$barangayName}%")->first();
            $barangayId = $brgy?->barangay_id;
        }

        $sitioId = null;
        if ($sitioName && $barangayId) {
            $sitio = Sitio::where('barangay_id', $barangayId)
                ->where('sitio_name', 'LIKE', "%{$sitioName}%")
                ->first();
            $sitioId = $sitio?->sitio_id;
        }

        $purokId = null;
        if ($purokName && $barangayId) {
            $purok = Purok::where('barangay_id', $barangayId)
                ->where('purok_name', 'LIKE', "%{$purokName}%")
                ->first();
            $purokId = $purok?->purok_id;
        }

        $fullAddress = $location['full_address'] ?? null;
        if (empty($fullAddress)) {
            $parts = [];
            if ($street) $parts[] = $street;
            if ($purokName) $parts[] = "Purok {$purokName}";
            if ($sitioName) $parts[] = "Sitio {$sitioName}";
            if ($barangayName) $parts[] = $barangayName;
            if ($cityName) $parts[] = $cityName;
            if ($provinceName) $parts[] = $provinceName;
            $fullAddress = implode(', ', array_filter($parts));
        }

        if ($addressId) {
            $address = Address::find($addressId);
            if ($address) {
                if ($street) {
                    $address->street = $street;
                    $address->street_address = $street;
                }
                if ($barangayId) $address->barangay_id = $barangayId;
                if ($barangayName) $address->barangay_name = $barangayName;
                if ($sitioId) $address->sitio_id = $sitioId;
                if ($sitioName) $address->purok_sitio = $sitioName;
                if ($purokId) $address->purok_id = $purokId;
                if (!empty($fullAddress)) $address->full_address = $fullAddress;
                $address->save();
            }
        } elseif (!empty($fullAddress) || $barangayId || $street) {
            $newId = ((int) Address::max('address_id')) + 1;
            $address = Address::create([
                'address_id'     => $newId,
                'street'         => $street,
                'street_address' => $street,
                'barangay_id'    => $barangayId,
                'barangay_name'  => $barangayName,
                'sitio_id'       => $sitioId,
                'purok_id'       => $purokId,
                'purok_sitio'    => $sitioName ?: $purokName,
                'full_address'   => $fullAddress,
            ]);
            $addressId = $newId;
        }

        // 2. Resolve member count
        $membersData = $item['members'] ?? [];
        $memberCount = is_array($membersData) && count($membersData) > 0
            ? count($membersData)
            : (int) ($item['member_count'] ?? 0);

        // 3. Upsert Household into local database
        $household = Household::updateOrCreate(
            ['household_id' => $householdId],
            [
                'household_code'    => $item['household_code'] ?? $householdId,
                'household_number'  => $item['household_number'] ?? null,
                'household_name'    => $item['household_name'] ?? 'Unknown Household',
                'contact_number'    => $item['contact_number'] ?? null,
                'emergency_contact' => $item['emergency_contact'] ?? null,
                'email'             => $item['email'] ?? null,
                'member_count'      => $memberCount,
                'address_id'        => $addressId,
            ]
        );

        // 4. Upsert Members if provided
        if (is_array($membersData) && !empty($membersData)) {
            foreach ($membersData as $index => $m) {
                $memberId = $m['member_id'] ?? $m['id'] ?? null;
                if (!$memberId) {
                    $memberId = "HM-{$householdId}-" . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
                }

                // Name mapping
                $firstName = $m['first_name'] ?? null;
                $middleName = $m['middle_name'] ?? null;
                $lastName = $m['last_name'] ?? null;
                $fullName = $m['name'] ?? $m['full_name'] ?? null;

                if (empty($firstName) && !empty($fullName)) {
                    $nameParts = explode(' ', trim($fullName));
                    if (count($nameParts) === 1) {
                        $firstName = $nameParts[0];
                    } else {
                        $lastName = array_pop($nameParts);
                        $firstName = implode(' ', $nameParts);
                    }
                }

                if (empty($fullName)) {
                    $fullName = implode(' ', array_filter([$firstName, $middleName, $lastName]));
                }

                // Birth date and age calculation
                $birthDate = $m['birth_date'] ?? $m['dob'] ?? null;
                $age = isset($m['age']) && is_numeric($m['age']) ? (int) $m['age'] : null;

                if ($birthDate) {
                    try {
                        $parsedDate = Carbon::parse($birthDate);
                        $birthDate = $parsedDate->format('Y-m-d');
                        if ($age === null) {
                            $age = $parsedDate->age;
                        }
                    } catch (\Throwable) {
                        $birthDate = '1990-01-01';
                    }
                } elseif ($age !== null) {
                    $birthDate = Carbon::now()->subYears($age)->format('Y-01-01');
                } else {
                    $birthDate = '1990-01-01';
                }

                // Gender mapping
                $genderRaw = strtolower((string) ($m['gender'] ?? $m['sex'] ?? ''));
                $genderId = $m['gender_id'] ?? null;
                if (!$genderId) {
                    if (str_starts_with($genderRaw, 'm')) {
                        $genderId = 1;
                    } elseif (str_starts_with($genderRaw, 'f')) {
                        $genderId = 2;
                    } else {
                        $genderId = 1;
                    }
                }
                $genderLabel = $genderId === 2 ? 'Female' : 'Male';
                $sexChar = $genderId === 2 ? 'F' : 'M';

                // Relationship mapping
                $relationRaw = strtolower((string) ($m['relation'] ?? $m['relationship'] ?? ''));
                $relationshipId = $m['relationship_id'] ?? null;
                $isHead = !empty($m['is_household_head']) || $relationRaw === 'head';

                if (!$relationshipId) {
                    if ($isHead) {
                        $relationshipId = 1;
                        $relationLabel = 'Head';
                    } elseif (str_contains($relationRaw, 'spouse') || str_contains($relationRaw, 'wife') || str_contains($relationRaw, 'husband')) {
                        $relationshipId = 2;
                        $relationLabel = 'Spouse';
                    } elseif (str_contains($relationRaw, 'son')) {
                        $relationshipId = 3;
                        $relationLabel = 'Son';
                    } elseif (str_contains($relationRaw, 'daughter')) {
                        $relationshipId = 4;
                        $relationLabel = 'Daughter';
                    } elseif (str_contains($relationRaw, 'child')) {
                        $relationshipId = $genderId === 2 ? 4 : 3;
                        $relationLabel = 'Child';
                    } elseif (str_contains($relationRaw, 'father')) {
                        $relationshipId = 14;
                        $relationLabel = 'Father';
                    } elseif (str_contains($relationRaw, 'mother')) {
                        $relationshipId = 15;
                        $relationLabel = 'Mother';
                    } elseif (str_contains($relationRaw, 'brother')) {
                        $relationshipId = 22;
                        $relationLabel = 'Brother';
                    } elseif (str_contains($relationRaw, 'sister')) {
                        $relationshipId = 23;
                        $relationLabel = 'Sister';
                    } else {
                        $relationshipId = 38; // Non-relative / other
                        $relationLabel = $m['relation'] ?? 'Member';
                    }
                } else {
                    $relationLabel = $m['relation'] ?? ($relationshipId === 1 ? 'Head' : 'Member');
                }

                // Civil Status mapping
                $civilRaw = strtolower((string) ($m['civil_status'] ?? ''));
                $civilStatusId = $m['civil_status_id'] ?? null;
                if (!$civilStatusId) {
                    if (str_contains($civilRaw, 'marr')) {
                        $civilStatusId = 2;
                        $civilLabel = 'Married';
                    } elseif (str_contains($civilRaw, 'widow')) {
                        $civilStatusId = 3;
                        $civilLabel = 'Widowed';
                    } elseif (str_contains($civilRaw, 'separat')) {
                        $civilStatusId = 4;
                        $civilLabel = 'Separated';
                    } elseif (str_contains($civilRaw, 'divorc')) {
                        $civilStatusId = 5;
                        $civilLabel = 'Divorced';
                    } else {
                        $civilStatusId = 1;
                        $civilLabel = 'Single';
                    }
                } else {
                    $civilLabel = $m['civil_status'] ?? 'Single';
                }

                // Vulnerabilities
                $isSenior = !empty($m['is_senior']) || ($age !== null && $age >= 60) ? 1 : 0;
                $isPwd = !empty($m['is_pwd']) || !empty($m['pwd']) ? 1 : 0;
                $isPregnant = !empty($m['is_pregnant']) || !empty($m['pregnant']) ? 1 : 0;

                $member = HouseholdMember::updateOrCreate(
                    ['member_id' => $memberId],
                    [
                        'household_id'             => $householdId,
                        'first_name'               => $firstName,
                        'middle_name'              => $middleName,
                        'last_name'                => $lastName,
                        'name'                     => $fullName,
                        'birth_date'               => $birthDate,
                        'gender_id'                => $genderId,
                        'gender'                   => $genderLabel,
                        'sex'                      => $sexChar,
                        'relationship_id'          => $relationshipId,
                        'relation'                 => $relationLabel,
                        'civil_status_id'          => $civilStatusId,
                        'civil_status'             => $civilLabel,
                        'is_household_head'        => $isHead ? 1 : 0,
                        'can_report_for_household' => !empty($m['can_report_for_household']) || $isHead ? 1 : 0,
                        'is_pwd'                   => $isPwd,
                        'is_pregnant'              => $isPregnant,
                        'contact_number'           => $m['contact_number'] ?? null,
                        'occupation'               => $m['occupation'] ?? null,
                        'education_level'          => $m['education_level'] ?? null,
                        'religion'                 => $m['religion'] ?? null,
                        'special_needs'            => $m['special_needs'] ?? null,
                    ]
                );

                // Sync pivot table member_vulnerable_groups
                $vGroupIds = [];
                if ($isSenior) $vGroupIds[] = 1; // Elderly
                if ($age !== null && $age < 18) $vGroupIds[] = 2; // Children
                if ($isPwd) $vGroupIds[] = 3; // PWD
                if ($isPregnant) $vGroupIds[] = 4; // Pregnant Women

                if (!empty($vGroupIds)) {
                    $member->vulnerableGroupDetails()->syncWithoutDetaching($vGroupIds);
                }
            }
        }

        return $household;
    }
}
