<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\Notifications\Models\UrgencyLevel;

class UrgencyLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'urgency_key' => 'low',
                'urgency_label' => 'Low',
            ],
            [
                'urgency_key' => 'medium',
                'urgency_label' => 'Medium',
            ],
            [
                'urgency_key' => 'high',
                'urgency_label' => 'High',
            ],
            [
                'urgency_key' => 'critical',
                'urgency_label' => 'Critical',
            ],
        ];

        foreach ($levels as $level) {
            UrgencyLevel::firstOrCreate(
                ['urgency_key' => $level['urgency_key']],
                ['urgency_label' => $level['urgency_label']]
            );
        }
    }
}