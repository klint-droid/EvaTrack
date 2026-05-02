<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HouseholdStatusTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('household_status')->insert([
            ['status_key' => 'active', 'status_label' => 'Active'],
            ['status_key' => 'evacuated', 'status_label' => 'Evacuated'],
            ['status_key' => 'not_evacuated', 'status_label' => 'Not Evacuated'],
            ['status_key' => 'relocated', 'status_label' => 'Relocated'],
            ['status_key' => 'displaced', 'status_label' => 'Displaced'],
            ['status_key' => 'returned', 'status_label' => 'Returned'],
        ]);
    }
}