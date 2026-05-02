<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsJobStatusTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('analytics_job_status')->insert([
            ['status_key' => 'queued', 'status_label' => 'Queued'],
            ['status_key' => 'processing', 'status_label' => 'Processing'],
            ['status_key' => 'completed', 'status_label' => 'Completed'],
            ['status_key' => 'failed', 'status_label' => 'Failed'],
        ]);
    }
}