<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EvacPersonnelSeeder extends Seeder
{
    public function run(): void
    {
        $conn = DB::connection('mysql_v2');

        // Find an active evacuation center to assign personnel to
        $defaultCenter = $conn->table('evacuation_centers')->where('status', 'active')->first();
        $centerId = $defaultCenter?->evacuation_center_id ?? 'EC-2026-9M2PAG';

        $userId = 'EPE-2026-PERS1';
        $password = 'personnel123';
        $contact = '09888888888';

        $exists = $conn->table('users')->where('user_id', $userId)->first();

        $userData = [
            'first_name' => 'Evacuation',
            'last_name' => 'Personnel',
            'name' => 'Evacuation Personnel',
            'password' => Hash::make($password),
            'role_id' => 3, // evac_personnel
            'contact_number' => $contact,
            'assigned_center_id' => $centerId,
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

        // Also activate and standardize passwords for existing personnel accounts
        $conn->table('users')
            ->where('role_id', 3)
            ->whereIn('user_id', ['EPE-2026-JKSM7O', 'EPE-2026-A7ZGO8', 'EPE-2026-JICFBC', 'EPE-2026-UQBFZH', 'EPE-2026-PZYGCS'])
            ->update([
                'password' => Hash::make($password),
                'is_active' => 1,
                'updated_at' => now(),
            ]);
    }
}
