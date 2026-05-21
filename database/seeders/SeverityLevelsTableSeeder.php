<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeverityLevelsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mysql_v2')->table('severity_levels')->insertOrIgnore([
            ['severity_id' => 1, 'severity_key' => 'low', 'severity_label' => 'Low'],
            ['severity_id' => 2, 'severity_key' => 'medium', 'severity_label' => 'Medium'],
            ['severity_id' => 3, 'severity_key' => 'high', 'severity_label' => 'High'],
            ['severity_id' => 4, 'severity_key' => 'critical', 'severity_label' => 'Critical'],
        ]);
    }
}