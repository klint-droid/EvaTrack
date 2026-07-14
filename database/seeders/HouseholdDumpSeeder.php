<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Domains\Households\Models\Household;
use App\Domains\Households\Models\HouseholdMember;
use App\Domains\ReferenceData\Models\Address;

class HouseholdDumpSeeder extends Seeder
{
    public function run(): void
    {
        $addressId = Address::first()?->address_id;

        $maleGenderId = DB::table('genders')->where('gender_key', 'male')->value('gender_id') ?? 1;
        $femaleGenderId = DB::table('genders')->where('gender_key', 'female')->value('gender_id') ?? 2;

        $headRelId = DB::table('relationships')->where('relationship_key', 'head')->value('relationship_id') ?? 1;
        $spouseRelId = DB::table('relationships')->where('relationship_key', 'spouse')->value('relationship_id') ?? 2;
        $childRelId = DB::table('relationships')->where('relationship_key', 'child')->value('relationship_id') ?? 3;
        $parentRelId = DB::table('relationships')->where('relationship_key', 'parent')->value('relationship_id') ?? 4;
        $siblingRelId = DB::table('relationships')->where('relationship_key', 'sibling')->value('relationship_id') ?? 5;

        $singleStatusId = DB::table('civil_statuses')->where('status_key', 'single')->value('status_id') ?? 1;
        $marriedStatusId = DB::table('civil_statuses')->where('status_key', 'married')->value('status_id') ?? 2;

        $surnames = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Dela Cruz', 'Aquino', 'Garcia', 'Mendoza', 'Torres'];
        $maleNames = ['Juan', 'Jose', 'Manuel', 'Ricardo', 'Danilo', 'Antonio', 'Emilio', 'Gabriel', 'Michael', 'Christian'];
        $femaleNames = ['Maria', 'Corazon', 'Imelda', 'Angelica', 'Teresa', 'Christina', 'Patricia', 'Sophia', 'Isabella', 'Jasmine'];

        for ($i = 0; $i < 5; $i++) {
            $surname = $surnames[$i];
            $householdName = "{$surname} Family";
            
            // Create household
            $household = Household::create([
                'household_name' => $householdName,
                'address_id' => $addressId,
                'contact_number' => '0917' . rand(1000000, 9999999),
                'emergency_contact' => '0918' . rand(1000000, 9999999),
            ]);

            // Determine member count (5 to 10)
            $memberCount = rand(5, 10);
            
            // Create Head
            $headIsMale = rand(0, 1) === 1;
            $headFirstName = $headIsMale ? $maleNames[rand(0, 9)] : $femaleNames[rand(0, 9)];
            
            HouseholdMember::create([
                'household_id' => $household->household_id,
                'first_name' => $headFirstName,
                'middle_name' => 'A.',
                'last_name' => $surname,
                'birth_date' => now()->subYears(rand(40, 60))->format('Y-m-d'),
                'gender_id' => $headIsMale ? $maleGenderId : $femaleGenderId,
                'relationship_id' => $headRelId,
                'civil_status_id' => $marriedStatusId,
            ]);

            // Create Spouse (if we have at least 5 members)
            $hasSpouse = $memberCount >= 6;
            if ($hasSpouse) {
                $spouseFirstName = !$headIsMale ? $maleNames[rand(0, 9)] : $femaleNames[rand(0, 9)];
                HouseholdMember::create([
                    'household_id' => $household->household_id,
                    'first_name' => $spouseFirstName,
                    'middle_name' => 'B.',
                    'last_name' => $surname,
                    'birth_date' => now()->subYears(rand(35, 55))->format('Y-m-d'),
                    'gender_id' => !$headIsMale ? $maleGenderId : $femaleGenderId,
                    'relationship_id' => $spouseRelId,
                    'civil_status_id' => $marriedStatusId,
                ]);
            }

            // Create remaining as children
            $createdCount = $hasSpouse ? 2 : 1;
            while ($createdCount < $memberCount) {
                $childIsMale = rand(0, 1) === 1;
                $childFirstName = $childIsMale ? $maleNames[rand(0, 9)] : $femaleNames[rand(0, 9)];
                
                HouseholdMember::create([
                    'household_id' => $household->household_id,
                    'first_name' => $childFirstName,
                    'middle_name' => 'C.',
                    'last_name' => $surname,
                    'birth_date' => now()->subYears(rand(5, 25))->format('Y-m-d'),
                    'gender_id' => $childIsMale ? $maleGenderId : $femaleGenderId,
                    'relationship_id' => $childRelId,
                    'civil_status_id' => $singleStatusId,
                ]);
                $createdCount++;
            }
        }
    }
}
