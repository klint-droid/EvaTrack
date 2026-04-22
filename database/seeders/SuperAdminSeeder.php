<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
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
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'StrongPassword123!')),
                'role_id' => 1, 
                'contact_number' => env('SUPER_ADMIN_CONTACT', null),
                'assigned_center_id' => null,
            ]);
        }
    }
}