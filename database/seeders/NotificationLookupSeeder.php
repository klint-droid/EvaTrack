<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationLookupSeeder extends Seeder
{
    public function run(): void
    {
        // First truncate or clean up zero channel_id entries if any
        DB::connection('mysql_v2')->statement("DELETE FROM notification_channels WHERE channel_id = 0");
        DB::connection('mysql_v2')->statement("DELETE FROM notification_statuses WHERE status_id = 0");

        DB::connection('mysql_v2')->table('notification_channels')->upsert([
            ['channel_id' => 1, 'channel_key' => 'sms',  'channel_label' => 'SMS'],
            ['channel_id' => 2, 'channel_key' => 'push', 'channel_label' => 'Push Notification'],
            ['channel_id' => 3, 'channel_key' => 'both', 'channel_label' => 'SMS & Push'],
        ], ['channel_id'], ['channel_key', 'channel_label']);

        DB::connection('mysql_v2')->table('notification_statuses')->upsert([
            ['status_id' => 1, 'status_key' => 'sent',      'status_label' => 'Sent'],
            ['status_id' => 2, 'status_key' => 'failed',    'status_label' => 'Failed'],
            ['status_id' => 3, 'status_key' => 'pending',   'status_label' => 'Pending'],
            ['status_id' => 4, 'status_key' => 'scheduled', 'status_label' => 'Scheduled'],
            ['status_id' => 5, 'status_key' => 'cancelled', 'status_label' => 'Cancelled'],
        ], ['status_id'], ['status_key', 'status_label']);

        DB::connection('mysql_v2')->table('recurrence_types')->upsert([
            ['type_id' => 1, 'type_key' => 'hourly', 'type_label' => 'Hourly'],
            ['type_id' => 2, 'type_key' => 'daily',  'type_label' => 'Daily'],
            ['type_id' => 3, 'type_key' => 'weekly', 'type_label' => 'Weekly'],
        ], ['type_id'], ['type_key', 'type_label']);
    }
}