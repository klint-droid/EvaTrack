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

    private function ensureLookupsSeeded(): void
    {
        NotificationChannel::upsert([
            ['channel_id' => 1, 'channel_key' => 'sms',  'channel_label' => 'SMS'],
            ['channel_id' => 2, 'channel_key' => 'push', 'channel_label' => 'Push Notification'],
            ['channel_id' => 3, 'channel_key' => 'both', 'channel_label' => 'SMS & Push'],
        ], ['channel_id'], ['channel_key', 'channel_label']);

        NotificationStatus::upsert([
            ['status_id' => 1, 'status_key' => 'sent',      'status_label' => 'Sent'],
            ['status_id' => 2, 'status_key' => 'failed',    'status_label' => 'Failed'],
            ['status_id' => 3, 'status_key' => 'pending',   'status_label' => 'Pending'],
            ['status_id' => 4, 'status_key' => 'scheduled', 'status_label' => 'Scheduled'],
            ['status_id' => 5, 'status_key' => 'cancelled', 'status_label' => 'Cancelled'],
        ], ['status_id'], ['status_key', 'status_label']);

        \App\Domains\Notifications\Models\RecurrenceType::upsert([
            ['type_id' => 1, 'type_key' => 'hourly', 'type_label' => 'Hourly'],
            ['type_id' => 2, 'type_key' => 'daily',  'type_label' => 'Daily'],
            ['type_id' => 3, 'type_key' => 'weekly', 'type_label' => 'Weekly'],
            ['type_id' => 4, 'type_key' => 'custom', 'type_label' => 'Custom Interval'],
        ], ['type_id'], ['type_key', 'type_label']);
    }

    private function channelId(string $key): int
    {
        $id = NotificationChannel::where('channel_key', $key)->value('channel_id');
        if ($id === null) {
            $this->ensureLookupsSeeded();
            $id = NotificationChannel::where('channel_key', $key)->value('channel_id');
        }
        return (int) ($id ?? match ($key) {
            'sms'  => 1,
            'push' => 2,
            'both' => 3,
            default => 1,
        });
    }

    private function statusId(string $key): int
    {
        $id = NotificationStatus::where('status_key', $key)->value('status_id');
        if ($id === null) {
            $this->ensureLookupsSeeded();
            $id = NotificationStatus::where('status_key', $key)->value('status_id');
        }
        return (int) ($id ?? match ($key) {
            'sent'      => 1,
            'failed'    => 2,
            'pending'   => 3,
            'scheduled' => 4,
            'cancelled' => 5,
            default     => 1,
        });
    }
    public function dispatch(array $payload): Notification
    {
        // Auto-resolve active disaster event if not explicitly provided
        if (empty($payload['evacuation_event_id'])) {
            if (!empty($payload['evacuation_center_id'])) {
                $center = \App\Domains\EvacuationCenters\Models\EvacuationCenter::find($payload['evacuation_center_id']);
                $payload['evacuation_event_id'] = $center?->current_event_id;
            }
            if (empty($payload['evacuation_event_id'])) {
                $activeEvent = \App\Domains\EvacuationEvents\Models\DisasterEvent::whereNull('ended_at')->latest()->first();
                $payload['evacuation_event_id'] = $activeEvent?->event_id;
            }
        }

        $targetFilter = $payload['target_filter'] ?? 'all';
        $householdIds = $this->resolveRecipients(
            $targetFilter,
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
        $feedPayload = null;
        if ($isRecurring && !empty($payload['recurrence_type'])) {
            $this->ensureLookupsSeeded();
            $recurrenceTypeId = \App\Domains\Notifications\Models\RecurrenceType::where('type_key', $payload['recurrence_type'])->value('type_id');

            if ($payload['recurrence_type'] === 'custom') {
                $intervalVal = !empty($payload['recurrence_interval_value']) ? (int)$payload['recurrence_interval_value'] : 1;
                $intervalUnit = $payload['recurrence_interval_unit'] ?? 'hours';
                $minutes = match($intervalUnit) {
                    'minutes' => $intervalVal,
                    'hours'   => $intervalVal * 60,
                    'days'    => $intervalVal * 1440,
                    'weeks'   => $intervalVal * 10080,
                    default   => $intervalVal * 60,
                };
                if (!empty($payload['recurrence_interval_minutes'])) {
                    $minutes = (int)$payload['recurrence_interval_minutes'];
                }

                $feedPayload = [
                    'recurrence_type'             => 'custom',
                    'recurrence_interval_value'   => $intervalVal,
                    'recurrence_interval_unit'    => $intervalUnit,
                    'recurrence_interval_minutes' => max(1, $minutes),
                ];
            }
        }

        $notification = DB::connection('mysql_v2')->transaction(function () use ($payload, $householdIds, $channel, $status, $isRecurring, $recurrenceTypeId, $feedPayload) {

            $notification = Notification::create([
                'message'              => $payload['message'],
                'sent_by'              => $payload['sent_by'],
                'evacuation_event_id'  => $payload['evacuation_event_id']  ?? null,
                'evacuation_center_id' => $payload['evacuation_center_id'] ?? null,
                'scheduled_at'         => $payload['scheduled_at']         ?? null,
                'channel'              => $channel,
                'status'               => $status,
                'target_filter'        => $payload['target_filter'] ?? 'all',
                'is_recurring'         => $isRecurring,
                'recurrence_type_id'   => $recurrenceTypeId,
                'recurrence_end_at'    => $payload['recurrence_end_at'] ?? null,
                'feed_payload_json'    => $feedPayload,
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
            $customMinutes = $notification->recurrence_interval_minutes ?: ($feedPayload['recurrence_interval_minutes'] ?? 60);
            $nextRun = match($recurrenceType) {
                'hourly' => now()->addHour(),
                'daily'  => now()->addDay(),
                'weekly' => now()->addWeek(),
                'custom' => now()->addMinutes(max(1, (int)$customMinutes)),
                default  => now()->addDay(),
            };

            $recurrenceEndAt = !empty($payload['recurrence_end_at'])
                ? now()->parse($payload['recurrence_end_at'])
                : null;

            // only schedule next if before end date
            if (!$recurrenceEndAt || now()->parse($nextRun)->isBefore($recurrenceEndAt)) {
                // reset status to scheduled for the next run (since sendToChannels sets it to 'sent')
                $notification->update([
                    'status' => 'scheduled',
                    'scheduled_at' => $nextRun,
                ]);

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

    public function resolveRecipients(string $targetFilter, ?string $centerId, ?string $eventId): array
    {
        $query = Household::on('mysql_v2');

        if ($centerId) {
            // Target specific evacuation center
            $query->whereHas('currentEvacuation', fn($q) =>
                $q->where('center_id', $centerId)
            );
        } elseif ($targetFilter === 'not_evacuated') {
            // Only households that are NOT currently evacuated
            $query->whereDoesntHave('currentEvacuation');
        } elseif ($targetFilter === 'evacuated') {
            // Only households that are currently evacuated
            $query->whereHas('currentEvacuation', function ($q) use ($eventId) {
                if ($eventId) {
                    $q->where('event_id', $eventId);
                }
            });
        }
        // If $targetFilter === 'all' and no $centerId, no conditions applied -> all registered households

        return $query->pluck('household_id')->toArray();
    }

    protected function formatDetailedMessage(Notification $notification): array
    {
        $notification->loadMissing(['event', 'center']);

        $rawMessage = trim($notification->message);
        
        // Extract clean base message directive
        $cleanMessage = explode("\n\nEvent:", $rawMessage)[0];
        $cleanMessage = explode("\n\nCenter:", $cleanMessage)[0];
        $cleanMessage = explode("\n\nCenters:", $cleanMessage)[0];
        $cleanMessage = explode("\n\nLink:", $cleanMessage)[0];
        $cleanMessage = explode("\n\nhttp", $cleanMessage)[0];
        $cleanMessage = trim($cleanMessage);

        $event = $notification->event;
        if (!$event && $notification->center?->current_event_id) {
            $event = \App\Domains\EvacuationEvents\Models\DisasterEvent::find($notification->center->current_event_id);
        }
        if (!$event) {
            $event = \App\Domains\EvacuationEvents\Models\DisasterEvent::whereNull('ended_at')->latest()->first();
        }

        $eventName = $event?->name ?? 'General Emergency Alert';

        if ($notification->center) {
            $centerName  = $notification->center->name;
            $url         = "http://100.73.14.100:5173/evacuation-centers/{$notification->center->evacuation_center_id}";
            $centerLabel = "Center: {$centerName}";
        } else {
            $url         = "http://100.73.14.100:5173/public";
            $centerLabel = "Centers: All Evacuation Centers";
        }

        $asOfTime   = ($notification->created_at ?? now())->format('M j, Y, g:i A');
        $asOfHeader = "As of {$asOfTime} Announcement";

        $structuredDetails = [];
        $structuredDetails[] = $asOfHeader;
        $structuredDetails[] = "Event: {$eventName}";
        $structuredDetails[] = $centerLabel;
        $structuredDetails[] = $url;

        $finalMessage = $cleanMessage . "\n\n" . implode("\n", $structuredDetails);

        return [
            'message' => $finalMessage,
            'url'     => $url,
        ];
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

        $details = $this->formatDetailedMessage($notification);

        foreach ($households as $household) {
            $result = $this->sms->send($household->contact_number, $details['message']);

            NotificationLog::create([
                'notification_id'     => $notification->notif_id,
                'household_id'        => $household->household_id,
                'channel_id'          => $this->channelId('sms'),
                'status_id'           => $this->statusId($result['success'] ? 'sent' : 'failed'),
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
        $details = $this->formatDetailedMessage($notification);

        $result = $this->push->sendToPlayers(
            $playerIds,
            'Disaster Alert',
            $details['message'],
            [
                'notif_id'  => $notification->notif_id,
                'url'       => $details['url'],
                'center_id' => $notification->evacuation_center_id,
                'event_id'  => $notification->evacuation_event_id,
            ],
        );

        $channelId = $this->channelId('push');
        $statusId  = $this->statusId($result['success'] ? 'sent' : 'failed');

        $tokens->groupBy('household_id')
            ->each(function ($group, $householdId) use ($channelId, $notification, $result, $statusId) {
                NotificationLog::create([
                    'notification_id'     => $notification->notif_id,
                    'household_id'        => $householdId,
                    'channel_id'          => $channelId,
                    'status_id'           => $statusId,
                    'sent_at'             => $result['success'] ? now() : null,
                    'external_message_id' => $result['notification_id'] ?? null,
                ]);
            });
    }
}