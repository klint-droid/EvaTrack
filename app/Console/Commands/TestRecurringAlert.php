<?php

namespace App\Console\Commands;

use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\UrgencyLevel;
use App\Jobs\SendScheduledNotification;
use Illuminate\Console\Command;

class TestRecurringAlert extends Command
{
    protected $signature = 'test:recurring-alert {--minutes=1 : Delay interval in minutes} {--message=Test Recurring Alert}';
    protected $description = 'Dispatch a test recurring notification with a 1-minute (or custom minute) delay for testing without modifying production code';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $message = (string) $this->option('message');

        $urgency = UrgencyLevel::first();
        if (!$urgency) {
            $urgency = UrgencyLevel::create([
                'urgency_key' => 'low',
                'urgency_label' => 'Low',
            ]);
        }

        $notification = Notification::create([
            'message'          => "[TEST RECURRING] {$message} (" . now()->toTimeString() . ")",
            'sent_by'          => 1,
            'urgency_level_id' => $urgency->urgency_id,
            'channel'          => 'both',
            'status'           => 'scheduled',
            'target_filter'    => 'all',
            'is_recurring'     => true,
            'scheduled_at'     => null,
            'recurrence_end_at'=> now()->addDays(7),
        ]);

        $delay = now()->addMinutes($minutes);

        SendScheduledNotification::dispatch($notification->notif_id)->delay($delay);

        $this->info("Successfully created test recurring notification ID: {$notification->notif_id}");
        $this->info("Dispatched SendScheduledNotification delayed by {$minutes} minute(s) (scheduled for: " . $delay->toTimeString() . ")");
        $this->comment("\nTo process this job when the time arrives:");
        $this->line("1. Run: php artisan queue:work");
        $this->line("2. Or run: php artisan alerts:process-recurring");

        return Command::SUCCESS;
    }
}
