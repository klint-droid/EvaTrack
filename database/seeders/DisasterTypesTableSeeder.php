<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DisasterTypesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('disaster_types')->insert([
            [
                'type_code' => 'EQ',
                'type_name' => 'Earthquake',
                'severity_level' => 1, // Low
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'type_code' => 'FL',
                'type_name' => 'Flood',
                'severity_level' => 2, // Moderate
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'type_code' => 'TC',
                'type_name' => 'Tropical Cyclone',
                'severity_level' => 3, // High
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'type_code' => 'VF',
                'type_name' => 'Volcanic Eruption',
                'severity_level' => 4, // Critical
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);
    }
}