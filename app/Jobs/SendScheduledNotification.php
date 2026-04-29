<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendScheduledNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $notifId) {}

    public function handle(NotificationService $service): void
    {
        $notification = Notification::with('recipients')
            ->where('notif_id', $this->notifId)
            ->where('status', 'scheduled')
            ->first();

        if (!$notification) return; // cancelled or already sent

        // check if recurring has expired
        if ($notification->is_recurring && $notification->recurrence_end_at) {
            if (now()->isAfter($notification->recurrence_end_at)) {
                $notification->update(['status' => 'sent']);
                return;
            }
        }

        $householdIds = $notification->recipients->pluck('household_id')->toArray();

        $channels = match($notification->channel) {
            'sms'  => ['sms'],
            'push' => ['push'],
            'both' => ['sms', 'push'],
        };

        $service->sendToChannels($notification, $householdIds, $channels);

        // update last_sent_at
        $notification->update(['last_sent_at' => now()]);

        // if recurring, schedule next run
        if ($notification->is_recurring) {
            $nextRun = match($notification->recurrence_type) {
                'hourly' => now()->addHour(),
                'daily'  => now()->addDay(),
                'weekly' => now()->addWeek(),
            };

            // only schedule next if before end date
            if (!$notification->recurrence_end_at || now()->parse($nextRun)->isBefore($notification->recurrence_end_at)) {
                // reset status to scheduled for next run
                $notification->update(['status' => 'scheduled']);

                self::dispatch($this->notifId)->delay($nextRun);
            }
        }
    }
}