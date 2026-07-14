<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Domains\Authentication\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EvacAdminSeeder extends Seeder
{
    public function run(): void
    {
        $contact = env('EVAC_ADMIN_CONTACT', '09999999999');

        if (!User::where('contact_number', $contact)->exists()) {
            $userId = 'EAD-' . date('Y') . '-' . strtoupper(Str::random(6));
            User::create([
                'user_id' => $userId,
                'first_name' => 'Evacuation',
                'last_name' => 'Admin',
                'password' => Hash::make(env('EVAC_ADMIN_PASSWORD', 'admin123')),
                'role_id' => 2,
                'contact_number' => $contact,
                'assigned_center_id' => null,
            ]);
        }
    }
}
