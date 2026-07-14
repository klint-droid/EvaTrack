<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\Authentication\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (!User::where('role_id', 1)->exists()) {
            $userId = 'SUP-' . date('Y') . '-' . strtoupper(Str::random(6));

            User::create([
                'user_id' => $userId,
                'first_name' => 'System',
                'last_name' => 'Admin',
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'StrongPassword123!')),
                'role_id' => 1, 
                'contact_number' => env('SUPER_ADMIN_CONTACT', null),
                'assigned_center_id' => null,
            ]);
        }
    }
}