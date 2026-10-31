<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvacuationCenterSeeder extends Seeder
{
    public function run(): void
    {
        $conn = DB::connection('mysql_v2');

        $centers = [
            [
                'evacuation_center_id' => 'EC-2026-9M2PAG',
                'name' => 'Mambaling Elementary School',
                'latitude' => 10.2915141,
                'longitude' => 123.8777902,
                'capacity' => 150,
                'status' => 'active',
                'current_occupancy' => 0,
                'osm_address' => 'Mambaling Flyover, Maria Gochan Subdivision, Mambaling, Cebu City, Central Visayas, 6000, Philippines',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'evacuation_center_id' => 'EC-2026-BDAARS',
                'name' => 'Mambaling Barangay Gymnasium / Covered Court',
                'latitude' => 10.2897152,
                'longitude' => 123.8765572,
                'capacity' => 400,
                'status' => 'active',
                'current_occupancy' => 0,
                'osm_address' => 'Maria Gochan Subdivision, Mambaling, Cebu City, Central Visayas, 6000, Philippines',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'evacuation_center_id' => 'EC-2026-KOR6F7',
                'name' => 'Alaska Elementary School',
                'latitude' => 10.2858551,
                'longitude' => 123.8741983,
                'capacity' => 250,
                'status' => 'active',
                'current_occupancy' => 0,
                'osm_address' => 'Basketball Court, Tabada Street, Alumnos, Basak San Nicolas, Cebu City, Central Visayas, 6000, Philippines',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'evacuation_center_id' => 'EC-2026-ZMYOKS',
                'name' => 'Mambaling National High School',
                'latitude' => 10.2935427,
                'longitude' => 123.8793042,
                'capacity' => 350,
                'status' => 'active',
                'current_occupancy' => 0,
                'osm_address' => 'Holy Child Dormitory, 235-F, Larima Way, Maria Gochan Subdivision, Mambaling, Cebu City, Central Visayas, 6000, Philippines',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'evacuation_center_id' => 'EC-2026-G7LY71',
                'name' => 'Mambaling Barangay Hall',
                'latitude' => 10.2909467,
                'longitude' => 123.8773907,
                'capacity' => 100,
                'status' => 'active',
                'current_occupancy' => 0,
                'osm_address' => 'F. Vestil Street, Maria Gochan Subdivision, Mambaling, Cebu City, Central Visayas, 6000, Philippines',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($centers as $center) {
            $conn->table('evacuation_centers')->updateOrInsert(
                ['evacuation_center_id' => $center['evacuation_center_id']],
                $center
            );
        }
    }
}

