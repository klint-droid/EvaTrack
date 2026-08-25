<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Domains\Households\Models\Household;
use App\Domains\Households\Models\HouseholdMember;

class TenCompleteHouseholdsSeeder extends Seeder
{
    public function run(): void
    {
        $conn = DB::connection('mysql_v2');

        // Lookups
        $maleGenderId = $conn->table('genders')->where('gender_key', 'male')->value('gender_id') ?? 1;
        $femaleGenderId = $conn->table('genders')->where('gender_key', 'female')->value('gender_id') ?? 2;

        $headRelId = $conn->table('relationships')->where('relationship_key', 'head')->value('relationship_id') ?? 1;
        $spouseRelId = $conn->table('relationships')->where('relationship_key', 'spouse')->value('relationship_id') ?? 2;
        $childRelId = $conn->table('relationships')->where('relationship_key', 'child')->value('relationship_id') ?? 3;
        $parentRelId = $conn->table('relationships')->where('relationship_key', 'parent')->value('relationship_id') ?? 4;
        $siblingRelId = $conn->table('relationships')->where('relationship_key', 'sibling')->value('relationship_id') ?? 5;

        $singleStatusId = $conn->table('civil_statuses')->where('status_key', 'single')->value('status_id') ?? 1;
        $marriedStatusId = $conn->table('civil_statuses')->where('status_key', 'married')->value('status_id') ?? 2;
        $widowedStatusId = $conn->table('civil_statuses')->where('status_key', 'widowed')->value('status_id') ?? 3;

        // Vulnerable Group IDs
        $elderlyVgId   = $conn->table('vulnerable_groups')->where('vulnerable_group_key', 'elderly')->value('vulnerable_group_id') ?? 1;
        $childrenVgId  = $conn->table('vulnerable_groups')->where('vulnerable_group_key', 'children')->value('vulnerable_group_id') ?? 2;
        $pwdVgId       = $conn->table('vulnerable_groups')->where('vulnerable_group_key', 'pwd')->value('vulnerable_group_id') ?? 3;
        $pregnantVgId  = $conn->table('vulnerable_groups')->where('vulnerable_group_key', 'pregnant')->value('vulnerable_group_id') ?? 4;

        // Find or create Mambaling Barangay & Sitio
        $barangay = $conn->table('barangays')->where('barangay_name', 'LIKE', '%Mambaling%')->first();
        $barangayId = $barangay ? $barangay->barangay_id : ($conn->table('barangays')->value('barangay_id') ?? 396);
        $barangayName = $barangay ? $barangay->barangay_name : 'Mambaling';
        $cityId = $barangay ? $barangay->city_id : 17;
        $zipcodeId = $conn->table('zipcodes')->where('city_id', $cityId)->value('zipcode_id') ?? 17;
        $zipCodeStr = $conn->table('zipcodes')->where('zipcode_id', $zipcodeId)->value('zipcode') ?? '6000';

        $sitioNames = [
            'Sitio Zapatera', 'Sitio Alaska', 'Sitio Kadasig', 'Sitio San Roque', 'Sitio Viking',
            'Sitio Dawis', 'Sitio Pag-utlan', 'Sitio Badjao', 'Sitio Ibabao', 'Sitio NHA'
        ];

        $sitioIds = [];
        foreach ($sitioNames as $sName) {
            $sitioId = $conn->table('sitios')->where('barangay_id', $barangayId)->where('sitio_name', $sName)->value('sitio_id');
            if (!$sitioId) {
                $sitioId = ($conn->table('sitios')->max('sitio_id') ?? 100) + 1;
                $conn->table('sitios')->insert([
                    'sitio_id' => $sitioId,
                    'sitio_name' => $sName,
                    'barangay_id' => $barangayId
                ]);
            }
            $sitioIds[] = $sitioId;
        }

        // 10 Detailed Household Definitions
        $householdsData = [
            [
                'name' => 'Santos Family',
                'street' => '104 Katipunan St.',
                'contact' => '09171234501',
                'emergency' => '09189876501',
                'sitio_index' => 0,
                'members' => [
                    ['first' => 'Roberto', 'middle' => 'Cruz', 'last' => 'Santos', 'age' => 45, 'gender' => $maleGenderId, 'rel' => $headRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Elena', 'middle' => 'Reyes', 'last' => 'Santos', 'age' => 42, 'gender' => $femaleGenderId, 'rel' => $spouseRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Mark Anthony', 'middle' => 'Reyes', 'last' => 'Santos', 'age' => 17, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Sophia Claire', 'middle' => 'Reyes', 'last' => 'Santos', 'age' => 12, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Consuelo', 'middle' => 'Bautista', 'last' => 'Santos', 'age' => 71, 'gender' => $femaleGenderId, 'rel' => $parentRelId, 'civil' => $widowedStatusId, 'vgs' => [$elderlyVgId]],
                ]
            ],
            [
                'name' => 'Dela Cruz Family',
                'street' => '45 Alaska Beach St.',
                'contact' => '09171234502',
                'emergency' => '09189876502',
                'sitio_index' => 1,
                'members' => [
                    ['first' => 'Juan', 'middle' => 'Perez', 'last' => 'Dela Cruz', 'age' => 38, 'gender' => $maleGenderId, 'rel' => $headRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Maria Theresa', 'middle' => 'Gonzales', 'last' => 'Dela Cruz', 'age' => 35, 'gender' => $femaleGenderId, 'rel' => $spouseRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Juanito Jr.', 'middle' => 'Gonzales', 'last' => 'Dela Cruz', 'age' => 14, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Angelica', 'middle' => 'Gonzales', 'last' => 'Dela Cruz', 'age' => 4, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                ]
            ],
            [
                'name' => 'Reyes Family',
                'street' => '21 Kadasig Road',
                'contact' => '09171234503',
                'emergency' => '09189876503',
                'sitio_index' => 2,
                'members' => [
                    ['first' => 'Danilo', 'middle' => 'Ocampo', 'last' => 'Reyes', 'age' => 50, 'gender' => $maleGenderId, 'rel' => $headRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Carmencita', 'middle' => 'Flores', 'last' => 'Reyes', 'age' => 48, 'gender' => $femaleGenderId, 'rel' => $spouseRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Gabriel', 'middle' => 'Flores', 'last' => 'Reyes', 'age' => 22, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$pwdVgId]],
                    ['first' => 'Isabella', 'middle' => 'Flores', 'last' => 'Reyes', 'age' => 16, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Daniel', 'middle' => 'Flores', 'last' => 'Reyes', 'age' => 10, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Lourdes', 'middle' => 'Mendoza', 'last' => 'Flores', 'age' => 74, 'gender' => $femaleGenderId, 'rel' => $parentRelId, 'civil' => $widowedStatusId, 'vgs' => [$elderlyVgId]],
                ]
            ],
            [
                'name' => 'Mendoza Family',
                'street' => '88 San Roque Ext.',
                'contact' => '09171234504',
                'emergency' => '09189876504',
                'sitio_index' => 3,
                'members' => [
                    ['first' => 'Manuel', 'middle' => 'Aquino', 'last' => 'Mendoza', 'age' => 41, 'gender' => $maleGenderId, 'rel' => $headRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Patricia', 'middle' => 'Torres', 'last' => 'Mendoza', 'age' => 36, 'gender' => $femaleGenderId, 'rel' => $spouseRelId, 'civil' => $marriedStatusId, 'vgs' => [$pregnantVgId]],
                    ['first' => 'Christian', 'middle' => 'Torres', 'last' => 'Mendoza', 'age' => 13, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Chloe Marie', 'middle' => 'Torres', 'last' => 'Mendoza', 'age' => 7, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Tomas', 'middle' => 'Aquino', 'last' => 'Mendoza', 'age' => 69, 'gender' => $maleGenderId, 'rel' => $parentRelId, 'civil' => $widowedStatusId, 'vgs' => [$elderlyVgId]],
                ]
            ],
            [
                'name' => 'Bautista Family',
                'street' => '12 Viking Compound',
                'contact' => '09171234505',
                'emergency' => '09189876505',
                'sitio_index' => 4,
                'members' => [
                    ['first' => 'Ricardo', 'middle' => 'Villamor', 'last' => 'Bautista', 'age' => 39, 'gender' => $maleGenderId, 'rel' => $headRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Jasmine', 'middle' => 'Salazar', 'last' => 'Bautista', 'age' => 37, 'gender' => $femaleGenderId, 'rel' => $spouseRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Rafael', 'middle' => 'Salazar', 'last' => 'Bautista', 'age' => 11, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Samantha', 'middle' => 'Salazar', 'last' => 'Bautista', 'age' => 6, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                ]
            ],
            [
                'name' => 'Aquino Family',
                'street' => '304 Dawis Ave.',
                'contact' => '09171234506',
                'emergency' => '09189876506',
                'sitio_index' => 5,
                'members' => [
                    ['first' => 'Antonio', 'middle' => 'Mercado', 'last' => 'Aquino', 'age' => 44, 'gender' => $maleGenderId, 'rel' => $headRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Corazon', 'middle' => 'Villanueva', 'last' => 'Aquino', 'age' => 40, 'gender' => $femaleGenderId, 'rel' => $spouseRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Bryan', 'middle' => 'Villanueva', 'last' => 'Aquino', 'age' => 15, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Baby Hannah', 'middle' => 'Villanueva', 'last' => 'Aquino', 'age' => 1, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Esperanza', 'middle' => 'Mercado', 'last' => 'Aquino', 'age' => 76, 'gender' => $femaleGenderId, 'rel' => $parentRelId, 'civil' => $widowedStatusId, 'vgs' => [$elderlyVgId]],
                ]
            ],
            [
                'name' => 'Fernandez Family',
                'street' => '55 Pag-utlan Lane',
                'contact' => '09171234507',
                'emergency' => '09189876507',
                'sitio_index' => 6,
                'members' => [
                    ['first' => 'Imelda', 'middle' => 'Castillo', 'last' => 'Fernandez', 'age' => 46, 'gender' => $femaleGenderId, 'rel' => $headRelId, 'civil' => $widowedStatusId, 'vgs' => []],
                    ['first' => 'Kevin', 'middle' => 'Castillo', 'last' => 'Fernandez', 'age' => 18, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => []],
                    ['first' => 'Kaitlyn', 'middle' => 'Castillo', 'last' => 'Fernandez', 'age' => 14, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                ]
            ],
            [
                'name' => 'Villanueva Family',
                'street' => '19 Badjao Coastal Rd.',
                'contact' => '09171234508',
                'emergency' => '09189876508',
                'sitio_index' => 7,
                'members' => [
                    ['first' => 'Jose', 'middle' => 'Tan', 'last' => 'Villanueva', 'age' => 47, 'gender' => $maleGenderId, 'rel' => $headRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Rowena', 'middle' => 'Garcia', 'last' => 'Villanueva', 'age' => 43, 'gender' => $femaleGenderId, 'rel' => $spouseRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Joshua', 'middle' => 'Garcia', 'last' => 'Villanueva', 'age' => 20, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => []],
                    ['first' => 'Justin', 'middle' => 'Garcia', 'last' => 'Villanueva', 'age' => 16, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Janine', 'middle' => 'Garcia', 'last' => 'Villanueva', 'age' => 11, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Jacob', 'middle' => 'Garcia', 'last' => 'Villanueva', 'age' => 5, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                ]
            ],
            [
                'name' => 'Gonzales Family',
                'street' => '77 Ibabao Alley',
                'contact' => '09171234509',
                'emergency' => '09189876509',
                'sitio_index' => 8,
                'members' => [
                    ['first' => 'Emilio', 'middle' => 'Navarro', 'last' => 'Gonzales', 'age' => 52, 'gender' => $maleGenderId, 'rel' => $headRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Christina', 'middle' => 'Ramos', 'last' => 'Gonzales', 'age' => 49, 'gender' => $femaleGenderId, 'rel' => $spouseRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Ethan', 'middle' => 'Ramos', 'last' => 'Gonzales', 'age' => 15, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId, $pwdVgId]],
                    ['first' => 'Sexto', 'middle' => 'Navarro', 'last' => 'Gonzales', 'age' => 78, 'gender' => $maleGenderId, 'rel' => $parentRelId, 'civil' => $widowedStatusId, 'vgs' => [$elderlyVgId]],
                ]
            ],
            [
                'name' => 'Mercado Family',
                'street' => '202 NHA Housing Block 4',
                'contact' => '09171234510',
                'emergency' => '09189876510',
                'sitio_index' => 9,
                'members' => [
                    ['first' => 'Michael', 'middle' => 'Cruz', 'last' => 'Mercado', 'age' => 42, 'gender' => $maleGenderId, 'rel' => $headRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Joy', 'middle' => 'Bautista', 'last' => 'Mercado', 'age' => 39, 'gender' => $femaleGenderId, 'rel' => $spouseRelId, 'civil' => $marriedStatusId, 'vgs' => []],
                    ['first' => 'Matthew', 'middle' => 'Bautista', 'last' => 'Mercado', 'age' => 16, 'gender' => $maleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Megan', 'middle' => 'Bautista', 'last' => 'Mercado', 'age' => 12, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                    ['first' => 'Mia', 'middle' => 'Bautista', 'last' => 'Mercado', 'age' => 8, 'gender' => $femaleGenderId, 'rel' => $childRelId, 'civil' => $singleStatusId, 'vgs' => [$childrenVgId]],
                ]
            ],
        ];

        $insertedHouseholds = 0;
        $insertedMembers = 0;
        $nextAddressId = ($conn->table('addresses')->max('address_id') ?? 1000) + 1;

        foreach ($householdsData as $hData) {
            $sitioId = $sitioIds[$hData['sitio_index']];
            $sitioName = $sitioNames[$hData['sitio_index']];
            $fullAddressStr = "{$hData['street']}, {$sitioName}, {$barangayName}, Cebu City, {$zipCodeStr}";

            // 1. Create Address
            $addressId = $nextAddressId++;
            $conn->table('addresses')->insert([
                'address_id' => $addressId,
                'street' => $hData['street'],
                'barangay_id' => $barangayId,
                'barangay_name' => $barangayName,
                'sitio_id' => $sitioId,
                'purok_sitio' => $sitioName,
                'zipcode_id' => $zipcodeId,
                'zip_code' => $zipCodeStr,
                'full_address' => $fullAddressStr,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Create Household
            $household = Household::create([
                'household_name' => $hData['name'],
                'address_id' => $addressId,
                'contact_number' => $hData['contact'],
                'emergency_contact' => $hData['emergency'],
                'member_count' => count($hData['members']),
            ]);

            $insertedHouseholds++;

            // 3. Create Members
            foreach ($hData['members'] as $m) {
                $birthDate = now()->subYears($m['age'])->subMonths(rand(1, 11))->format('Y-m-d');

                $member = HouseholdMember::create([
                    'household_id' => $household->household_id,
                    'first_name' => $m['first'],
                    'middle_name' => $m['middle'],
                    'last_name' => $m['last'],
                    'birth_date' => $birthDate,
                    'gender_id' => $m['gender'],
                    'relationship_id' => $m['rel'],
                    'civil_status_id' => $m['civil'],
                ]);

                $insertedMembers++;

                // Attach vulnerable groups
                if (!empty($m['vgs'])) {
                    foreach ($m['vgs'] as $vgId) {
                        $conn->table('member_vulnerable_groups')->insert([
                            'member_id' => $member->member_id,
                            'vulnerable_group_id' => $vgId,
                        ]);
                    }
                }
            }
        }

        $this->command->info("Successfully seeded {$insertedHouseholds} complete households with {$insertedMembers} members!");
    }
}
