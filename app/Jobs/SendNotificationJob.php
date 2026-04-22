<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\SmsGateService;
use App\Services\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notificationId;

    /**
     * Create a new job instance.
     */
    public function __construct($notificationId)
    {
        $this->notificationId = $notificationId;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsGateService $smsService, PushService $pushService)
    {
        $notification = Notification::with('recipients.household')
            ->find($this->notificationId);

        if (!$notification) {
            \Log::error('Notification not found', [
                'notification_id' => $this->notificationId
            ]);
            return;
        }

        foreach ($notification->recipients as $recipient) {

            $household = $recipient->household;

            if (!$household) {
                continue;
            }

            $number = $household->contact_number;

            // 🔒 Skip if no contact number
            if (!$number) {
                \Log::warning('Missing contact number', [
                    'household_id' => $household->household_id
                ]);
                continue;
            }

            try {
                // 📡 SEND SMS
                $smsService->sendSMS(
                    $number,
                    $notification->message,
                    $notification->notif_id,
                    $household->household_id
                );

                // 📲 SEND PUSH (optional)
                $pushService->send(
                    $notification,
                    $household->household_id
                );

            } catch (\Exception $e) {
                \Log::error('Notification send failed', [
                    'household_id' => $household->household_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}