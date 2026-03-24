<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::where('email', env('SUPER_ADMIN_EMAIL'))->exists()) {
            User::create([
                'name' => env('SUPER_ADMIN_NAME'),
                'email' => env('SUPER_ADMIN_EMAIL'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD')),
                'role' => 'super_admin'
            ]);
        }
    }
}
