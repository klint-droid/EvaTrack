<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed all roles, centers, and system users (Super Admin, Evacuation Admins, Personnel).
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            EvacuationCenterSeeder::class,
            SuperAdminSeeder::class,
            EvacAdminSeeder::class,
            EvacPersonnelSeeder::class,
        ]);
    }
}

