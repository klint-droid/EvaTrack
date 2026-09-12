<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'role_id' => 1,
                'role_key' => 'super_admin',
                'role_name' => 'Super Admin',
                'name' => 'Super Admin',
            ],
            [
                'role_id' => 2,
                'role_key' => 'evac_admin',
                'role_name' => 'Evacuation Admin',
                'name' => 'Evacuation Admin',
            ],
            [
                'role_id' => 3,
                'role_key' => 'evac_personnel',
                'role_name' => 'Evacuation Personnel',
                'name' => 'Evacuation Personnel',
            ],
        ];

        foreach ($roles as $role) {
            DB::connection('mysql_v2')->table('roles')->updateOrInsert(
                ['role_id' => $role['role_id']],
                $role
            );
        }
    }
}
