<?php

namespace App\Console\Commands;

use App\Domains\Notifications\Models\Notification;
use App\Jobs\SendScheduledNotification;
use Illuminate\Console\Command;

class ProcessRecurringAlerts extends Command
{
    protected $signature = 'alerts:process-recurring';
    protected $description = 'Process due recurring and scheduled disaster alerts';

    public function handle(): int
    {
        $dueAlerts = Notification::where('status', 'scheduled')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->get();

        if ($dueAlerts->isEmpty()) {
            $this->info('No due recurring or scheduled alerts to process.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$dueAlerts->count()} due alert(s)...");

        foreach ($dueAlerts as $alert) {
            SendScheduledNotification::dispatchSync($alert->notif_id);
            $this->line("Processed alert: {$alert->notif_id}");
        }

        $this->info('Due alerts processing complete.');
        return Command::SUCCESS;
    }
}
