<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HouseholdStatusTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mysql_v2')->table('household_statuses')->upsert([
            ['status_id' => 1, 'status_key' => 'active',        'status_label' => 'Active'],
            ['status_id' => 2, 'status_key' => 'evacuated',     'status_label' => 'Evacuated'],
            ['status_id' => 3, 'status_key' => 'not_evacuated', 'status_label' => 'Not Evacuated'],
            ['status_id' => 4, 'status_key' => 'relocated',     'status_label' => 'Relocated'],
            ['status_id' => 5, 'status_key' => 'displaced',     'status_label' => 'Displaced'],
            ['status_id' => 6, 'status_key' => 'returned',      'status_label' => 'Returned'],
        ], ['status_id'], ['status_key', 'status_label']);
    }
}