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

        $defaultCenter = $conn->table('evacuation_centers')->where('status', 'active')->first();
        $fallbackCenterId = $defaultCenter?->evacuation_center_id ?? null;

        $password = env('EVAC_PERSONNEL_PASSWORD', 'personnel123');
        $hashedPassword = Hash::make($password);

        $personnelList = [
            [
                'user_id' => 'EPE-2026-PERS1',
                'first_name' => 'Evacuation',
                'last_name' => 'Personnel',
                'name' => 'Evacuation Personnel',
                'contact_number' => '09888888888',
                'preferred_center_id' => 'EC-2026-9M2PAG',
            ],
            [
                'user_id' => 'EPE-2026-JKSM7O',
                'first_name' => 'Klint',
                'last_name' => 'Ruales',
                'name' => 'Klint Ruales',
                'contact_number' => '09553710861',
                'preferred_center_id' => 'EC-2026-9M2PAG',
            ],
            [
                'user_id' => 'EPE-2026-A7ZGO8',
                'first_name' => 'Anna',
                'last_name' => 'Villadolid',
                'name' => 'Anna Villadolid',
                'contact_number' => '09274481629',
                'preferred_center_id' => 'EC-2026-BDAARS',
            ],
            [
                'user_id' => 'EPE-2026-JICFBC',
                'first_name' => 'Kent',
                'last_name' => 'John Navarro',
                'name' => 'Kent John Navarro',
                'contact_number' => '09261735931',
                'preferred_center_id' => 'EC-2026-KOR6F7',
            ],
            [
                'user_id' => 'EPE-2026-UQBFZH',
                'first_name' => 'Mark',
                'last_name' => 'Zucker',
                'name' => 'Mark Zucker',
                'contact_number' => '09363488085',
                'preferred_center_id' => 'EC-2026-BDAARS',
            ],
            [
                'user_id' => 'EPE-2026-PZYGCS',
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'name' => 'Maria Santos',
                'contact_number' => '+639111111111',
                'preferred_center_id' => null, // Unassigned station
            ],
        ];

        foreach ($personnelList as $p) {
            // Verify preferred center exists in database before setting assigned_center_id
            $assignedCenterId = null;
            if ($p['preferred_center_id']) {
                $centerExists = $conn->table('evacuation_centers')
                    ->where('evacuation_center_id', $p['preferred_center_id'])
                    ->exists();
                if ($centerExists) {
                    $assignedCenterId = $p['preferred_center_id'];
                }
            }

            if (!$assignedCenterId && $p['preferred_center_id'] && $fallbackCenterId) {
                $assignedCenterId = $fallbackCenterId;
            }

            $userData = [
                'user_id' => $p['user_id'],
                'first_name' => $p['first_name'],
                'last_name' => $p['last_name'],
                'name' => $p['name'],
                'password' => $hashedPassword,
                'role_id' => 3, // evac_personnel
                'contact_number' => $p['contact_number'],
                'assigned_center_id' => $assignedCenterId,
                'is_active' => 1,
                'updated_at' => now(),
            ];

            $exists = $conn->table('users')->where('user_id', $p['user_id'])->first();

            if ($exists) {
                $conn->table('users')->where('user_id', $p['user_id'])->update($userData);
            } else {
                $userData['created_at'] = now();
                $conn->table('users')->insert($userData);
            }
        }

        // Activate all personnel accounts
        $conn->table('users')
            ->where('role_id', 3)
            ->where('is_active', 0)
            ->update(['is_active' => 1]);
    }
}
