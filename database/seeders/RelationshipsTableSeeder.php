<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RelationshipsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('relationships')->insert([
            ['relationship_key' => 'head', 'relationship_label' => 'Head of Household'],
            ['relationship_key' => 'spouse', 'relationship_label' => 'Spouse'],
            ['relationship_key' => 'child', 'relationship_label' => 'Child'],
            ['relationship_key' => 'parent', 'relationship_label' => 'Parent'],
            ['relationship_key' => 'sibling', 'relationship_label' => 'Sibling'],
            ['relationship_key' => 'other', 'relationship_label' => 'Other Relative'],
        ]);
    }
}