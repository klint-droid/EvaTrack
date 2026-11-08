<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResourceRequestStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mysql_v2')->table('resource_request_status')->upsert([
            ['status_id' => 1, 'status_key' => 'pending',      'status_label' => 'Pending'],
            ['status_id' => 2, 'status_key' => 'acknowledged', 'status_label' => 'Acknowledged'],
            ['status_id' => 3, 'status_key' => 'approved',     'status_label' => 'Approved'],
            ['status_id' => 4, 'status_key' => 'rejected',     'status_label' => 'Rejected'],
            ['status_id' => 5, 'status_key' => 'delivered',    'status_label' => 'Delivered'],
        ], ['status_id'], ['status_key', 'status_label']);
    }
}