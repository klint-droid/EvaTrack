<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Models\DeviceToken;
use App\Domains\Households\Models\Household;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationLog;
use App\Domains\Notifications\Models\NotificationRecipient;
use App\Domains\Notifications\Models\NotificationChannel;
use App\Domains\Notifications\Models\NotificationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        protected TextBeeService   $sms,
        protected OneSignalService $push,
    ) {}

    private function channelId(string $key): int
    {
        return NotificationChannel::where('channel_key', $key)->value('channel_id');
    }

    private function statusId(string $key): int
    {
        return NotificationStatus::where('status_key', $key)->value('status_id');
    }
    public function dispatch(array $payload): Notification
    {
        $householdIds = $this->resolveRecipients(
            $payload['target_filter']        ?? 'all',
            $payload['evacuation_center_id'] ?? null,
            $payload['evacuation_event_id']  ?? null,
        );

        $channel = $payload['channel'] ?? 'both';
        $channels = match($channel) {
            'sms'  => ['sms'],
            'push' => ['push'],
            'both' => ['sms', 'push'],
        };

        $isScheduled  = !empty($payload['scheduled_at']);
        $isRecurring  = !empty($payload['is_recurring']) && $payload['is_recurring'];
        $status       = ($isScheduled || $isRecurring) ? 'scheduled' : 'pending';

        $recurrenceTypeId = null;
        if ($isRecurring && !empty($payload['recurrence_type'])) {
            $recurrenceTypeId = \App\Domains\Notifications\Models\RecurrenceType::where('type_key', $payload['recurrence_type'])->value('type_id');
        }

        $notification = DB::connection('mysql_v2')->transaction(function () use ($payload, $householdIds, $channel, $status, $isRecurring, $recurrenceTypeId) {

            $notification = Notification::create([
                'message'              => $payload['message'],
                'sent_by'              => $payload['sent_by'],
                'urgency_level_id'     => $payload['urgency_level_id'],
                'evacuation_event_id'  => $payload['evacuation_event_id']  ?? null,
                'evacuation_center_id' => $payload['evacuation_center_id'] ?? null,
                'scheduled_at'         => $payload['scheduled_at']         ?? null,
                'channel'              => $channel,
                'status'               => $status,
                'target_filter'        => $payload['target_filter'] ?? 'all',
                'is_recurring'         => $isRecurring,
                'recurrence_type_id'   => $recurrenceTypeId,
                'recurrence_end_at'    => $payload['recurrence_end_at'] ?? null,
            ]);

            $rows = array_map(fn ($hid) => [
                'notification_id' => $notification->notif_id,
                'household_id'    => $hid,
                'read_at'         => null,
                'acknowledged_at' => null,
            ], $householdIds);

            if (!empty($rows)) {
                NotificationRecipient::insert($rows);
            }

            return $notification;
        });

        // if scheduled, queue it for the future date
        if ($isScheduled) {
            $sendAt = now()->parse($payload['scheduled_at']);

            \App\Jobs\SendScheduledNotification::dispatch($notification->notif_id)
                ->delay($sendAt);

            return $notification;
        }

        // send immediately
        $this->sendToChannels($notification, $householdIds, $channels);

        // if recurring, schedule the next run
        if ($isRecurring) {
            $notification->update(['last_sent_at' => now()]);

            $recurrenceType = $payload['recurrence_type'] ?? 'daily';
            $nextRun = match($recurrenceType) {
                'hourly' => now()->addHour(),
                'daily'  => now()->addDay(),
                'weekly' => now()->addWeek(),
                default  => now()->addDay(),
            };

            $recurrenceEndAt = !empty($payload['recurrence_end_at'])
                ? now()->parse($payload['recurrence_end_at'])
                : null;

            // only schedule next if before end date
            if (!$recurrenceEndAt || now()->parse($nextRun)->isBefore($recurrenceEndAt)) {
                // reset status to scheduled for the next run (since sendToChannels sets it to 'sent')
                $notification->update(['status' => 'scheduled']);

                \App\Jobs\SendScheduledNotification::dispatch($notification->notif_id)->delay($nextRun);
            }
        }

        return $notification;
    }

    public function sendToChannels(Notification $notification, array $householdIds, array $channels): void
    {
        if (in_array('sms', $channels)) {
            $this->dispatchSms($notification, $householdIds);
        }
        if (in_array('push', $channels)) {
            $this->dispatchPush($notification, $householdIds);
        }

        // update status
        $notification->update(['status' => 'sent']);
    }

    private function resolveRecipients(string $targetFilter, ?string $centerId, ?string $eventId): array
    {
        $query = Household::on('mysql_v2');

        if ($targetFilter === 'evacuated') {
            $query->whereHas('currentEvacuation');
        } elseif ($targetFilter === 'not_evacuated') {
            $query->whereDoesntHave('currentEvacuation');
        }

        if ($centerId) {
            $query->whereHas('currentEvacuation', fn($q) =>
                $q->where('center_id', $centerId)
            );
        }

        if ($eventId) {
            $query->whereHas('currentEvacuation', fn($q) =>
                $q->where('event_id', $eventId)
            );
        }

        return $query->pluck('household_id')->toArray();
    }

    protected function dispatchSms(Notification $notification, array $householdIds): void
    {
        $households = Household::on('mysql_v2')
            ->whereIn('household_id', $householdIds)
            ->whereNotNull('contact_number')
            ->get(['household_id', 'contact_number']);

        if ($households->isEmpty()) {
            Log::warning('[NotificationService] No households with contact_number found', [
                'notif_id' => $notification->notif_id,
            ]);
            return;
        }

        foreach ($households as $household) {
            $result = $this->sms->send($household->contact_number, $notification->message);

            NotificationLog::create([
                'notification_id'     => $notification->notif_id,
                'household_id'        => $household->household_id,
                'channel_id'             => $this->channelId('sms'),
                'status_id'              => $this->statusId($result['success'] ? 'sent' : 'failed'),
                'sent_at'             => $result['success'] ? now() : null,
                'external_message_id' => $result['message_id'] ?? null,
            ]);
        }
    }

    protected function dispatchPush(Notification $notification, array $householdIds): void
    {
        $tokens = DeviceToken::on('mysql_v2')
            ->whereIn('household_id', $householdIds)
            ->get(['household_id', 'player_id']);

        if ($tokens->isEmpty()) {
            Log::warning('[NotificationService] No device tokens found', [
                'notif_id' => $notification->notif_id,
            ]);
            return;
        }

        $playerIds = $tokens->pluck('player_id')->toArray();

        $result = $this->push->sendToPlayers(
            $playerIds,
            'Alert',
            $notification->message,
            ['notif_id' => $notification->notif_id],
        );

        $channelId = $this->channelId('push');
        $statusId  = $this->statusId($result['success'] ? 'sent' : 'failed');

        $tokens->groupBy('household_id')
            ->each(function ($group, $householdId) use ($channelId, $notification, $result, $statusId) {
                NotificationLog::create([
                    'notification_id'     => $notification->notif_id,
                    'household_id'        => $householdId,
                    'channel_id'             => $channelId,
                    'status_id'              => $statusId,
                    'sent_at'             => $result['success'] ? now() : null,
                    'external_message_id' => $result['notification_id'] ?? null,
                ]);
            });
    }
}