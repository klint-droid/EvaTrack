<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EvacAdminSeeder extends Seeder
{
    public function run(): void
    {
        $conn = DB::connection('mysql_v2');

        $userId = 'EAD-2026-ADMIN1';
        $password = env('EVAC_ADMIN_PASSWORD', 'admin123');
        $contact = env('EVAC_ADMIN_CONTACT', '09999999999');

        $exists = $conn->table('users')->where('user_id', $userId)->first();

        $userData = [
            'first_name' => 'Evacuation',
            'last_name' => 'Admin',
            'name' => 'Evacuation Admin',
            'password' => Hash::make($password),
            'role_id' => 2, // evac_admin
            'contact_number' => $contact,
            'assigned_center_id' => null,
            'is_active' => 1,
            'updated_at' => now(),
        ];

        if ($exists) {
            $conn->table('users')->where('user_id', $userId)->update($userData);
        } else {
            $userData['user_id'] = $userId;
            $userData['created_at'] = now();
            $conn->table('users')->insert($userData);
        }

        // Also ensure any existing legacy evac_admin accounts are activated
        $conn->table('users')
            ->where('role_id', 2)
            ->where('is_active', 0)
            ->update(['is_active' => 1]);
    }
}
