<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VulnerableGroupsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('vulnerable_groups')->insert([
            ['vulnerable_group_key' => 'elderly', 'vulnerable_group_label' => 'Elderly'],
            ['vulnerable_group_key' => 'children', 'vulnerable_group_label' => 'Children'],
            ['vulnerable_group_key' => 'pwd', 'vulnerable_group_label' => 'PWD'],
            ['vulnerable_group_key' => 'pregnant', 'vulnerable_group_label' => 'Pregnant Women'],
            ['vulnerable_group_key' => 'indigenous', 'vulnerable_group_label' => 'Indigenous People'],
        ]);
    }
}