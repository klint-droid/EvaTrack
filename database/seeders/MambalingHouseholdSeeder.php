<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MambalingHouseholdSeeder extends Seeder
{
    public function run(): void
    {
        $conn = DB::connection('mysql_v2');

        // 1. Get Barangay Mambaling (ID 396)
        $barangay = $conn->table('barangays')->where('barangay_name', 'LIKE', '%Mambaling%')->first();
        if (!$barangay) {
            $this->command->error("Barangay Mambaling not found!");
            return;
        }
        $barangayId = $barangay->barangay_id;
        $barangayName = $barangay->barangay_name ?? 'Mambaling';

        // Zipcode for Cebu City (city_id: 17)
        $zipcodeId = $conn->table('zipcodes')->where('city_id', $barangay->city_id)->value('zipcode_id') ?? 17;
        $zipCodeStr = $conn->table('zipcodes')->where('zipcode_id', $zipcodeId)->value('zipcode') ?? '6000';

        // 2. Sitios in Mambaling
        $sitioNames = [
            'Sitio Zapatera',
            'Sitio Kamanggahan',
            'Sitio Kadasig',
            'Sitio San Roque',
            'Sitio Alaska',
            'Sitio Viking',
            'Sitio Dawis',
            'Sitio Pag-utlan',
            'Sitio Badjao',
            'Sitio Ibabao',
            'Sitio NHA',
            'Sitio Puntod',
            'Sitio Haw-an'
        ];

        $nextSitioId = ($conn->table('sitios')->max('sitio_id') ?? 709) + 1;
        $sitioMap = [];
        foreach ($sitioNames as $sName) {
            $sitio = $conn->table('sitios')
                ->where('barangay_id', $barangayId)
                ->where('sitio_name', $sName)
                ->first();

            if (!$sitio) {
                $sitioId = $nextSitioId++;
                $conn->table('sitios')->insert([
                    'sitio_id' => $sitioId,
                    'sitio_name' => $sName,
                    'barangay_id' => $barangayId
                ]);
            } else {
                $sitioId = $sitio->sitio_id;
            }
            $sitioMap[$sName] = $sitioId;
        }

        // 3. Puroks for each Sitio in Mambaling
        $nextPurokId = ($conn->table('puroks')->max('purok_id') ?? 0) + 1;
        $purokMap = []; // sitio_id => [ ['id' => x, 'name' => 'Purok 1'] ]
        foreach ($sitioMap as $sName => $sId) {
            $purokMap[$sId] = [];
            for ($p = 1; $p <= 4; $p++) {
                $pName = "Purok $p";
                $purok = $conn->table('puroks')
                    ->where('sitio_id', $sId)
                    ->where('purok_name', $pName)
                    ->first();

                if (!$purok) {
                    $purokId = $nextPurokId++;
                    $conn->table('puroks')->insert([
                        'purok_id' => $purokId,
                        'purok_name' => $pName,
                        'sitio_id' => $sId
                    ]);
                } else {
                    $purokId = $purok->purok_id;
                }
                $purokMap[$sId][] = ['id' => $purokId, 'name' => $pName];
            }
        }

        // 4. Lookups
        $maleGenderId = $conn->table('genders')->where('gender_key', 'male')->value('gender_id') ?? 1;
        $femaleGenderId = $conn->table('genders')->where('gender_key', 'female')->value('gender_id') ?? 2;

        $headRelId = $conn->table('relationships')->where('relationship_key', 'head')->value('relationship_id') ?? 1;
        $spouseRelId = $conn->table('relationships')->where('relationship_key', 'spouse')->value('relationship_id') ?? 2;
        $childRelId = $conn->table('relationships')->where('relationship_key', 'child')->value('relationship_id') ?? 3;
        $parentRelId = $conn->table('relationships')->where('relationship_key', 'parent')->value('relationship_id') ?? 4;
        $siblingRelId = $conn->table('relationships')->where('relationship_key', 'sibling')->value('relationship_id') ?? 5;
        $otherRelId = $conn->table('relationships')->where('relationship_key', 'other')->value('relationship_id') ?? 6;

        $singleStatusId = $conn->table('civil_statuses')->where('status_key', 'single')->value('status_id') ?? 1;
        $marriedStatusId = $conn->table('civil_statuses')->where('status_key', 'married')->value('status_id') ?? 2;
        $widowedStatusId = $conn->table('civil_statuses')->where('status_key', 'widowed')->value('status_id') ?? 3;
        $separatedStatusId = $conn->table('civil_statuses')->where('status_key', 'separated')->value('status_id') ?? 4;

        $elderlyVgId = $conn->table('vulnerable_groups')->where('vulnerable_group_key', 'elderly')->value('vulnerable_group_id') ?? 1;
        $childrenVgId = $conn->table('vulnerable_groups')->where('vulnerable_group_key', 'children')->value('vulnerable_group_id') ?? 2;
        $pwdVgId = $conn->table('vulnerable_groups')->where('vulnerable_group_key', 'pwd')->value('vulnerable_group_id') ?? 3;
        $pregnantVgId = $conn->table('vulnerable_groups')->where('vulnerable_group_key', 'pregnant')->value('vulnerable_group_id') ?? 4;
        $indigenousVgId = $conn->table('vulnerable_groups')->where('vulnerable_group_key', 'indigenous')->value('vulnerable_group_id') ?? 5;

        // ALL households start fresh as NOT EVACUATED
        $householdsData = [
            [
                'surname' => 'Abellana',
                'sitio' => 'Sitio Alaska',
                'street' => 'Alaska Beach Road',
                'contact' => '09173214567',
                'emergency' => '09189871122',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Roberto', 'middle' => 'M.', 'age' => 54, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Elena', 'middle' => 'A.', 'age' => 52, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Jomar', 'middle' => 'A.', 'age' => 24, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Rhea', 'middle' => 'A.', 'age' => 19, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['pregnant']],
                    ['first' => 'Kevin', 'middle' => 'A.', 'age' => 14, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Rosa', 'middle' => 'M.', 'age' => 79, 'gender' => 'female', 'rel' => 'parent', 'civil' => 'widowed', 'vulnerable' => ['elderly']],
                ]
            ],
            [
                'surname' => 'Bontilao',
                'sitio' => 'Sitio Viking',
                'street' => 'Viking Street',
                'contact' => '09204567891',
                'emergency' => '09198765432',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Francisco', 'middle' => 'G.', 'age' => 46, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Luzviminda', 'middle' => 'B.', 'age' => 44, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Dave', 'middle' => 'B.', 'age' => 17, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Princess', 'middle' => 'B.', 'age' => 12, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Angel', 'middle' => 'B.', 'age' => 8, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children', 'pwd']],
                ]
            ],
            [
                'surname' => 'Cabahug',
                'sitio' => 'Sitio Dawis',
                'street' => 'Dawis St.',
                'contact' => '09321122334',
                'emergency' => '09435566778',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Junjun', 'middle' => 'R.', 'age' => 38, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Maricar', 'middle' => 'C.', 'age' => 35, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => ['pregnant']],
                    ['first' => 'Mark', 'middle' => 'C.', 'age' => 10, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Joy', 'middle' => 'C.', 'age' => 6, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Leo', 'middle' => 'C.', 'age' => 2, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ],
            [
                'surname' => 'Daclan',
                'sitio' => 'Sitio Kadasig',
                'street' => 'Kadasig St.',
                'contact' => '09178899001',
                'emergency' => '09181122334',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Carmelo', 'middle' => 'D.', 'age' => 65, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => ['elderly', 'pwd']],
                    ['first' => 'Concepcion', 'middle' => 'P.', 'age' => 63, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => ['elderly']],
                    ['first' => 'Jessel', 'middle' => 'D.', 'age' => 32, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Bryan', 'middle' => 'D.', 'age' => 28, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                ]
            ],
            [
                'surname' => 'Elorde',
                'sitio' => 'Sitio Badjao',
                'street' => 'Badjao Coastal Rd.',
                'contact' => '09224455667',
                'emergency' => '09337788990',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Bari', 'middle' => 'S.', 'age' => 42, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => ['indigenous']],
                    ['first' => 'Suraida', 'middle' => 'E.', 'age' => 39, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => ['indigenous']],
                    ['first' => 'Alamin', 'middle' => 'E.', 'age' => 15, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children', 'indigenous']],
                    ['first' => 'Fatima', 'middle' => 'E.', 'age' => 11, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children', 'indigenous']],
                    ['first' => 'Rashid', 'middle' => 'E.', 'age' => 7, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children', 'indigenous']],
                    ['first' => 'Aisha', 'middle' => 'E.', 'age' => 4, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children', 'indigenous']],
                ]
            ],
            [
                'surname' => 'Fernandez',
                'sitio' => 'Sitio Zapatera',
                'street' => 'Zapatera Interior St.',
                'contact' => '09179988776',
                'emergency' => '09186655443',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Ramon', 'middle' => 'T.', 'age' => 50, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Grace', 'middle' => 'F.', 'age' => 48, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Anthony', 'middle' => 'F.', 'age' => 22, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Stephanie', 'middle' => 'F.', 'age' => 18, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                ]
            ],
            [
                'surname' => 'Gonzales',
                'sitio' => 'Sitio Kamanggahan',
                'street' => 'Kamanggahan Alley',
                'contact' => '09281234567',
                'emergency' => '09297654321',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Vicente', 'middle' => 'L.', 'age' => 68, 'gender' => 'male', 'rel' => 'head', 'civil' => 'widowed', 'vulnerable' => ['elderly']],
                    ['first' => 'Maria', 'middle' => 'G.', 'age' => 39, 'gender' => 'female', 'rel' => 'child', 'civil' => 'separated', 'vulnerable' => []],
                    ['first' => 'Joshua', 'middle' => 'G.', 'age' => 13, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ],
            [
                'surname' => 'Hernandez',
                'sitio' => 'Sitio San Roque',
                'street' => 'San Roque Chapel Road',
                'contact' => '09171112233',
                'emergency' => '09184445566',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Godofredo', 'middle' => 'H.', 'age' => 57, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Teresita', 'middle' => 'M.', 'age' => 55, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Gideon', 'middle' => 'M.', 'age' => 26, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Giselle', 'middle' => 'M.', 'age' => 21, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Consuelo', 'middle' => 'H.', 'age' => 83, 'gender' => 'female', 'rel' => 'parent', 'civil' => 'widowed', 'vulnerable' => ['elderly', 'pwd']],
                ]
            ],
            [
                'surname' => 'Inocian',
                'sitio' => 'Sitio Pag-utlan',
                'street' => 'Pag-utlan Rd.',
                'contact' => '09209998877',
                'emergency' => '09213334455',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Danilo', 'middle' => 'K.', 'age' => 45, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Rosario', 'middle' => 'I.', 'age' => 43, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Christian', 'middle' => 'I.', 'age' => 16, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Christine', 'middle' => 'I.', 'age' => 11, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ],
            [
                'surname' => 'Jubay',
                'sitio' => 'Sitio Ibabao',
                'street' => 'N. Bacalso Ave. Ext.',
                'contact' => '09175556677',
                'emergency' => '09188889900',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Reynaldo', 'middle' => 'J.', 'age' => 39, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Analyn', 'middle' => 'J.', 'age' => 37, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Ethan', 'middle' => 'J.', 'age' => 9, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Chloe', 'middle' => 'J.', 'age' => 5, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ],
            [
                'surname' => 'Labrada',
                'sitio' => 'Sitio NHA',
                'street' => 'NHA Housing Block 3',
                'contact' => '09332221100',
                'emergency' => '09445556677',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Ernesto', 'middle' => 'L.', 'age' => 62, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => ['elderly']],
                    ['first' => 'Imelda', 'middle' => 'S.', 'age' => 60, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => ['elderly']],
                    ['first' => 'Jay', 'middle' => 'S.', 'age' => 30, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Janice', 'middle' => 'S.', 'age' => 27, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                ]
            ],
            [
                'surname' => 'Macachor',
                'sitio' => 'Sitio Puntod',
                'street' => 'Puntod Coastal St.',
                'contact' => '09170001122',
                'emergency' => '09183334455',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Rogelio', 'middle' => 'M.', 'age' => 51, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Lilibeth', 'middle' => 'O.', 'age' => 49, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Roy', 'middle' => 'O.', 'age' => 23, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Rhea', 'middle' => 'O.', 'age' => 20, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Rene', 'middle' => 'O.', 'age' => 15, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ],
            [
                'surname' => 'Navarro',
                'sitio' => 'Sitio Alaska',
                'street' => 'C. Padilla St. Ext.',
                'contact' => '09201114455',
                'emergency' => '09216667788',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Wilfredo', 'middle' => 'N.', 'age' => 47, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Rowena', 'middle' => 'E.', 'age' => 45, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Wayne', 'middle' => 'E.', 'age' => 19, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Wendy', 'middle' => 'E.', 'age' => 14, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Wilmer', 'middle' => 'E.', 'age' => 10, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children', 'pwd']],
                ]
            ],
            [
                'surname' => 'Ortega',
                'sitio' => 'Sitio Viking',
                'street' => 'F. Llamas St.',
                'contact' => '09176669988',
                'emergency' => '09182223344',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Salvador', 'middle' => 'O.', 'age' => 71, 'gender' => 'male', 'rel' => 'head', 'civil' => 'widowed', 'vulnerable' => ['elderly']],
                    ['first' => 'Sylvia', 'middle' => 'O.', 'age' => 41, 'gender' => 'female', 'rel' => 'child', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Edgar', 'middle' => 'V.', 'age' => 43, 'gender' => 'male', 'rel' => 'other', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Samantha', 'middle' => 'O.', 'age' => 12, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ],
            [
                'surname' => 'Pacquiao',
                'sitio' => 'Sitio Dawis',
                'street' => 'Dawis Main Rd.',
                'contact' => '09228887766',
                'emergency' => '09335554433',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Manuel', 'middle' => 'P.', 'age' => 43, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Jovita', 'middle' => 'P.', 'age' => 40, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Manny Jr.', 'middle' => 'P.', 'age' => 17, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Jinkee', 'middle' => 'P.', 'age' => 13, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Michael', 'middle' => 'P.', 'age' => 8, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ],
            [
                'surname' => 'Quijano',
                'sitio' => 'Sitio Haw-an',
                'street' => 'Haw-an St.',
                'contact' => '09177778899',
                'emergency' => '09189990011',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Benjamin', 'middle' => 'Q.', 'age' => 53, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Nenita', 'middle' => 'C.', 'age' => 50, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Benjie', 'middle' => 'C.', 'age' => 25, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Bernadette', 'middle' => 'C.', 'age' => 21, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                ]
            ],
            [
                'surname' => 'Rosales',
                'sitio' => 'Sitio Kadasig',
                'street' => 'Kadasig Interior',
                'contact' => '09205556677',
                'emergency' => '09218889900',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Eugenio', 'middle' => 'R.', 'age' => 36, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Jacqueline', 'middle' => 'M.', 'age' => 34, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => ['pregnant']],
                    ['first' => 'Earl', 'middle' => 'M.', 'age' => 7, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Evelyn', 'middle' => 'M.', 'age' => 3, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ],
            [
                'surname' => 'Sabinay',
                'sitio' => 'Sitio Badjao',
                'street' => 'Badjao Shoreline',
                'contact' => '09331112233',
                'emergency' => '09444445566',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Jalil', 'middle' => 'B.', 'age' => 40, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => ['indigenous']],
                    ['first' => 'Rufaida', 'middle' => 'S.', 'age' => 37, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => ['indigenous']],
                    ['first' => 'Tarik', 'middle' => 'S.', 'age' => 14, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children', 'indigenous']],
                    ['first' => 'Sherna', 'middle' => 'S.', 'age' => 9, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children', 'indigenous']],
                ]
            ],
            [
                'surname' => 'Teleron',
                'sitio' => 'Sitio Zapatera',
                'street' => 'Tabada St. Corner',
                'contact' => '09172223344',
                'emergency' => '09185556677',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Severino', 'middle' => 'T.', 'age' => 66, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => ['elderly']],
                    ['first' => 'Perla', 'middle' => 'G.', 'age' => 64, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => ['elderly']],
                    ['first' => 'Stanley', 'middle' => 'G.', 'age' => 34, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                ]
            ],
            [
                'surname' => 'Villamor',
                'sitio' => 'Sitio Kamanggahan',
                'street' => 'Kamanggahan Main St.',
                'contact' => '09208889900',
                'emergency' => '09211112233',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Armando', 'middle' => 'V.', 'age' => 48, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Fe', 'middle' => 'L.', 'age' => 46, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Alvin', 'middle' => 'L.', 'age' => 20, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Alyssa', 'middle' => 'L.', 'age' => 16, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                    ['first' => 'Adrian', 'middle' => 'L.', 'age' => 12, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ],
            [
                'surname' => 'Ybañez',
                'sitio' => 'Sitio San Roque',
                'street' => 'San Roque Heights',
                'contact' => '09174445566',
                'emergency' => '09187778899',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Leopoldo', 'middle' => 'Y.', 'age' => 58, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Glenda', 'middle' => 'P.', 'age' => 56, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Lance', 'middle' => 'P.', 'age' => 27, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Lorraine', 'middle' => 'P.', 'age' => 23, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Generosa', 'middle' => 'Y.', 'age' => 82, 'gender' => 'female', 'rel' => 'parent', 'civil' => 'widowed', 'vulnerable' => ['elderly', 'pwd']],
                ]
            ],
            [
                'surname' => 'Zafra',
                'sitio' => 'Sitio Pag-utlan',
                'street' => 'Pag-utlan Crossroad',
                'contact' => '09207776655',
                'emergency' => '09214443322',
                'status' => 'not_evacuated',
                'center_id' => null,
                'members' => [
                    ['first' => 'Mario', 'middle' => 'Z.', 'age' => 44, 'gender' => 'male', 'rel' => 'head', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Clara', 'middle' => 'C.', 'age' => 42, 'gender' => 'female', 'rel' => 'spouse', 'civil' => 'married', 'vulnerable' => []],
                    ['first' => 'Mark', 'middle' => 'C.', 'age' => 18, 'gender' => 'male', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => []],
                    ['first' => 'Maia', 'middle' => 'C.', 'age' => 15, 'gender' => 'female', 'rel' => 'child', 'civil' => 'single', 'vulnerable' => ['children']],
                ]
            ]
        ];

        // Map relationship keys to IDs and Labels
        $relMap = [
            'head' => ['id' => $headRelId, 'label' => 'Head of Household'],
            'spouse' => ['id' => $spouseRelId, 'label' => 'Spouse'],
            'child' => ['id' => $childRelId, 'label' => 'Child'],
            'parent' => ['id' => $parentRelId, 'label' => 'Parent'],
            'sibling' => ['id' => $siblingRelId, 'label' => 'Sibling'],
            'other' => ['id' => $otherRelId, 'label' => 'Other Relative'],
        ];

        $genderMap = [
            'male' => ['id' => $maleGenderId, 'label' => 'Male', 'sex' => 'M'],
            'female' => ['id' => $femaleGenderId, 'label' => 'Female', 'sex' => 'F'],
        ];

        $civilMap = [
            'single' => ['id' => $singleStatusId, 'label' => 'Single'],
            'married' => ['id' => $marriedStatusId, 'label' => 'Married'],
            'widowed' => ['id' => $widowedStatusId, 'label' => 'Widowed'],
            'separated' => ['id' => $separatedStatusId, 'label' => 'Separated'],
        ];

        $vgMap = [
            'elderly' => $elderlyVgId,
            'children' => $childrenVgId,
            'pwd' => $pwdVgId,
            'pregnant' => $pregnantVgId,
            'indigenous' => $indigenousVgId,
        ];

        $nextAddressId = ($conn->table('addresses')->max('address_id') ?? 597) + 1;

        foreach ($householdsData as $hData) {
            $sitioId = $sitioMap[$hData['sitio']];
            $purokObj = $purokMap[$sitioId][array_rand($purokMap[$sitioId])];
            $purokId = $purokObj['id'];
            $purokName = $purokObj['name'];

            $purokSitioStr = "{$hData['sitio']}, $purokName";
            $fullAddressStr = "{$hData['street']}, $purokSitioStr, $barangayName, Cebu City, $zipCodeStr";

            // Create Address
            $addressId = $nextAddressId++;
            $conn->table('addresses')->insert([
                'address_id' => $addressId,
                'street' => $hData['street'],
                'barangay_id' => $barangayId,
                'barangay_name' => $barangayName,
                'sitio_id' => $sitioId,
                'purok_sitio' => $purokSitioStr,
                'purok_id' => $purokId,
                'zipcode_id' => $zipcodeId,
                'zip_code' => $zipCodeStr,
                'full_address' => $fullAddressStr,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Unique household ID
            do {
                $householdId = 'NHH-' . date('Y') . '-' . strtoupper(Str::random(6));
            } while ($conn->table('households')->where('household_id', $householdId)->exists());

            $householdName = "{$hData['surname']} Family";
            $memberCount = count($hData['members']);

            // Create Household (Status: Fresh & Not Evacuated)
            $conn->table('households')->insert([
                'household_id' => $householdId,
                'household_code' => $householdId,
                'household_name' => $householdName,
                'address_id' => $addressId,
                'contact_number' => $hData['contact'],
                'emergency_contact' => $hData['emergency'],
                'member_count' => $memberCount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $createdMemberIds = [];

            // Create Members
            foreach ($hData['members'] as $m) {
                do {
                    $memberId = 'HM-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while ($conn->table('household_members')->where('member_id', $memberId)->exists());

                $birthDate = now()->subYears($m['age'])->subDays(rand(1, 300))->format('Y-m-d');
                $fullName = "{$m['first']} {$m['middle']} {$hData['surname']}";

                $gInfo = $genderMap[$m['gender']] ?? $genderMap['male'];
                $rInfo = $relMap[$m['rel']] ?? $relMap['child'];
                $cInfo = $civilMap[$m['civil']] ?? $civilMap['single'];

                $isSenior = in_array('elderly', $m['vulnerable']) || $m['age'] >= 60 ? 1 : 0;
                $isPwd = in_array('pwd', $m['vulnerable']) ? 1 : 0;
                $isPregnant = in_array('pregnant', $m['vulnerable']) ? 1 : 0;

                $conn->table('household_members')->insert([
                    'member_id' => $memberId,
                    'household_id' => $householdId,
                    'first_name' => $m['first'],
                    'middle_name' => $m['middle'],
                    'last_name' => $hData['surname'],
                    'birth_date' => $birthDate,
                    'gender_id' => $gInfo['id'],
                    'relationship_id' => $rInfo['id'],
                    'civil_status_id' => $cInfo['id'],
                    'is_senior' => $isSenior,
                    'is_pwd' => $isPwd,
                    'is_pregnant' => $isPregnant,
                    'name' => $fullName,
                    'sex' => $gInfo['sex'],
                    'gender' => $gInfo['label'],
                    'age' => $m['age'],
                    'relation' => $rInfo['label'],
                    'civil_status' => $cInfo['label'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdMemberIds[] = $memberId;

                // Sync Vulnerable Groups
                foreach ($m['vulnerable'] as $vgKey) {
                    if (isset($vgMap[$vgKey])) {
                        $conn->table('member_vulnerable_groups')->insert([
                            'member_id' => $memberId,
                            'vulnerable_group_id' => $vgMap[$vgKey]
                        ]);
                    }
                }
            }
        }

        $this->command->info("Successfully populated fresh Mambaling households (Not Evacuated)!");
    }
}
