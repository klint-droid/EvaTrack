<?php

namespace App\Jobs;

use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationRecipient;
use App\Domains\Notifications\Services\NotificationService;
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

        if (!$notification) return; // cancelled, completed, or non-existent

        // check if recurring has expired
        if ($notification->is_recurring && $notification->recurrence_end_at) {
            if (now()->isAfter($notification->recurrence_end_at)) {
                $notification->update(['status' => 'completed']);
                return;
            }
        }

        // Dynamically resolve recipients for current run (so newly evacuated households are included)
        $householdIds = $service->resolveRecipients(
            $notification->target_filter ?? 'all',
            $notification->evacuation_center_id,
            $notification->evacuation_event_id
        );

        if (empty($householdIds)) {
            $householdIds = $notification->recipients->pluck('household_id')->toArray();
        } else {
            // Ensure any new recipient records exist in notification_recipients
            $existingHids = $notification->recipients->pluck('household_id')->toArray();
            $newHids = array_diff($householdIds, $existingHids);
            if (!empty($newHids)) {
                $newRows = array_map(fn ($hid) => [
                    'notification_id' => $notification->notif_id,
                    'household_id'    => $hid,
                    'read_at'         => null,
                    'acknowledged_at' => null,
                ], $newHids);
                NotificationRecipient::insert($newRows);
            }
        }

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
            $recurrenceType = $notification->recurrence_type ?? 'daily';
            $nextRun = match($recurrenceType) {
                'hourly' => now()->addHour(),
                'daily'  => now()->addDay(),
                'weekly' => now()->addWeek(),
                default  => now()->addDay(),
            };

            // only schedule next if before end date
            if (!$notification->recurrence_end_at || now()->parse($nextRun)->isBefore($notification->recurrence_end_at)) {
                // reset status to scheduled for next run
                $notification->update(['status' => 'scheduled']);
                self::dispatch($this->notifId)->delay($nextRun);
            } else {
                $notification->update(['status' => 'completed']);
            }
        }
    }
}