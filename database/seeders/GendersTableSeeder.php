<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GendersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('genders')->insert([
            ['gender_key' => 'male', 'gender_label' => 'Male'],
            ['gender_key' => 'female', 'gender_label' => 'Female'],
            ['gender_key' => 'other', 'gender_label' => 'Other'],
        ]);
    }
}