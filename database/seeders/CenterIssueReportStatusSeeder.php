<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CenterIssueReportStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mysql_v2')->table('center_issue_report_statuses')->insertOrIgnore([
            ['status_id' => 1, 'status_key' => 'open', 'status_label' => 'Open'],
            ['status_id' => 2, 'status_key' => 'in_progress', 'status_label' => 'In Progress'],
            ['status_id' => 3, 'status_key' => 'resolved', 'status_label' => 'Resolved'],
            ['status_id' => 4, 'status_key' => 'closed', 'status_label' => 'Closed'],
        ]);
    }
}
