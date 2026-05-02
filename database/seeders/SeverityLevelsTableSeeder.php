<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeverityLevelsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('severity_levels')->insert([
            ['severity_key' => 'low', 'severity_label' => 'Low'],
            ['severity_key' => 'moderate', 'severity_label' => 'Moderate'],
            ['severity_key' => 'high', 'severity_label' => 'High'],
            ['severity_key' => 'critical', 'severity_label' => 'Critical'],
        ]);
    }
}