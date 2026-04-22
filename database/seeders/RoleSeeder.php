<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mysql_v2')->table('roles')->insert([
            [
                'role_id' => 1,
                'role_key' => 'super_admin',
                'role_name' => 'Super Admin',
            ],
            [
                'role_id' => 2,
                'role_key' => 'evac_admin',
                'role_name' => 'Evacuation Center Admin',
            ],
            [
                'role_id' => 3,
                'role_key' => 'evac_personnel',
                'role_name' => 'Evacuation Center Personnel',
            ],
        ]);
    }
}
