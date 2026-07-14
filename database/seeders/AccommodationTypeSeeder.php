<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\AccommodationUnits\Models\AccommodationType;

class AccommodationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['type_key' => 'family_room',   'type_label' => 'Family Room'],
            ['type_key' => 'dormitory',      'type_label' => 'Dormitory'],
            ['type_key' => 'tent',           'type_label' => 'Tent'],
            ['type_key' => 'classroom',      'type_label' => 'Classroom'],
            ['type_key' => 'gymnasium',      'type_label' => 'Gymnasium'],
            ['type_key' => 'covered_court',  'type_label' => 'Covered Court'],
        ];

        foreach ($types as $type) {
            AccommodationType::firstOrCreate(
                ['type_key' => $type['type_key']],
                ['type_label' => $type['type_label']]
            );
        }
    }
}