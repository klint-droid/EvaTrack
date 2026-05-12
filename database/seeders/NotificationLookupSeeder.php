<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationLookupSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mysql_v2')->table('notification_channels')->insertOrIgnore([
            ['channel_key' => 'sms',  'channel_label' => 'SMS'],
            ['channel_key' => 'push', 'channel_label' => 'Push Notification'],
            ['channel_key' => 'both', 'channel_label' => 'SMS & Push'],
        ]);

        DB::connection('mysql_v2')->table('notification_statuses')->insertOrIgnore([
            ['status_key' => 'sent',      'status_label' => 'Sent'],
            ['status_key' => 'failed',    'status_label' => 'Failed'],
            ['status_key' => 'pending',   'status_label' => 'Pending'],
            ['status_key' => 'scheduled', 'status_label' => 'Scheduled'],
            ['status_key' => 'cancelled', 'status_label' => 'Cancelled'],
        ]);
    }
}