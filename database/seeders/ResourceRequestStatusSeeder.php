<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResourceRequestStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mysql_v2')->table('resource_request_status')->insert([
            ['status_key' => 'pending',      'status_label' => 'Pending'],
            ['status_key' => 'acknowledged', 'status_label' => 'Acknowledged'],
            ['status_key' => 'approved',     'status_label' => 'Approved'],
            ['status_key' => 'rejected',     'status_label' => 'Rejected'],
            ['status_key' => 'delivered',    'status_label' => 'Delivered'],
        ]);
    }
}