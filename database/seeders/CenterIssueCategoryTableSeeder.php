<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CenterIssueCategoryTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('center_issue_categories')->insert([
            ['category_key' => 'shelter', 'category_label' => 'Shelter'],
            ['category_key' => 'food', 'category_label' => 'Food & Nutrition'],
            ['category_key' => 'health', 'category_label' => 'Health & Medical'],
            ['category_key' => 'water', 'category_label' => 'Water'],
            ['category_key' => 'sanitation', 'category_label' => 'Sanitation'],
            ['category_key' => 'protection', 'category_label' => 'Protection'],
        ]);
    }
}