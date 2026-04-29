<?php

namespace App\Console\Commands;

use App\Services\OneSignalService;
use Illuminate\Console\Command;

class TestOneSignal extends Command
{
    protected $signature = 'onesignal:test
                            {player_id : OneSignal player/subscription ID to send to}
                            {--title= : Custom title (defaults to a test string)}
                            {--message= : Custom message (defaults to a test string)}';

    protected $description = 'Send a test push notification via OneSignal to verify the integration';

    public function handle(OneSignalService $push): int
    {
        $playerId = $this->argument('player_id');
        $title    = $this->option('title')   ?? 'Test Notification 🔔';
        $message  = $this->option('message') ?? 'Test push from OneSignal integration ✅';

        $this->info("Sending push notification to player ID: {$playerId}...");

        $result = $push->sendToPlayers([$playerId], $title, $message);

        if ($result['success']) {
            $this->info('Push notification sent successfully!');
            $this->line('   Notification ID : ' . ($result['notification_id'] ?? 'n/a'));
        } else {
            $this->error('Push failed: ' . $result['error']);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}