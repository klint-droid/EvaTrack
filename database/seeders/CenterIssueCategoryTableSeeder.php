<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CenterIssueCategoryTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mysql_v2')->table('center_issue_categories')->insertOrIgnore([
            ['category_id' => 1, 'category_key' => 'incident', 'category_label' => 'Incident'],
            ['category_id' => 2, 'category_key' => 'facility_issue', 'category_label' => 'Facility Issue'],
            ['category_id' => 3, 'category_key' => 'health_issue', 'category_label' => 'Health Issue'],
            ['category_id' => 4, 'category_key' => 'safety_issue', 'category_label' => 'Safety Issue'],
            ['category_id' => 5, 'category_key' => 'other', 'category_label' => 'Other'],
        ]);
    }
}