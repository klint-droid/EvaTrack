<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CivilStatus;

class CivilStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['status_key' => 'single',    'status_label' => 'Single'],
            ['status_key' => 'married',   'status_label' => 'Married'],
            ['status_key' => 'widowed',   'status_label' => 'Widowed'],
            ['status_key' => 'separated', 'status_label' => 'Separated'],
            ['status_key' => 'divorced',  'status_label' => 'Divorced'],
            ['status_key' => 'annulled',  'status_label' => 'Annulled'],
        ];

        foreach ($statuses as $status) {
            CivilStatus::updateOrCreate(
                ['status_key' => $status['status_key']],
                ['status_label' => $status['status_label']]
            );
        }
    }
}
