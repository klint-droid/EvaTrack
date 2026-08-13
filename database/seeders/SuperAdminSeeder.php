<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $conn = DB::connection('mysql_v2');

        $userId = 'SUP-2026-SUPER1';
        $password = 'superadmin123';

        $exists = $conn->table('users')->where('user_id', $userId)->first();

        if ($exists) {
            $conn->table('users')->where('user_id', $userId)->update([
                'first_name' => 'System',
                'last_name' => 'Super Admin',
                'name' => 'System Super Admin',
                'password' => Hash::make($password),
                'role_id' => 1, // super_admin
                'contact_number' => '09123456789',
                'assigned_center_id' => null,
                'is_active' => 1,
                'updated_at' => now(),
            ]);
        } else {
            $conn->table('users')->insert([
                'user_id' => $userId,
                'first_name' => 'System',
                'last_name' => 'Super Admin',
                'name' => 'System Super Admin',
                'password' => Hash::make($password),
                'role_id' => 1, // super_admin
                'contact_number' => '09123456789',
                'assigned_center_id' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}