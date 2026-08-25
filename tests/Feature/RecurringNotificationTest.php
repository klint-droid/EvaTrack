<?php

namespace Tests\Feature;

use App\Console\Commands\TestRecurringAlert;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\UrgencyLevel;
use App\Domains\Notifications\Services\NotificationService;
use App\Jobs\SendScheduledNotification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecurringNotificationTest extends TestCase
{
    public function test_artisan_command_dispatches_1_minute_recurring_notification(): void
    {
        Queue::fake();

        $urgency = UrgencyLevel::first();
        if (!$urgency) {
            $urgency = UrgencyLevel::create([
                'urgency_key' => 'low',
                'urgency_label' => 'Low',
            ]);
        }

        $this->artisan('test:recurring-alert', ['--minutes' => 1])
            ->assertExitCode(0);

        $notification = Notification::where('message', 'like', '[TEST RECURRING]%')->latest()->first();
        $this->assertNotNull($notification);
        $this->assertTrue((bool) $notification->is_recurring);
        $this->assertEquals('scheduled', $notification->status);

        Queue::assertPushed(SendScheduledNotification::class, function ($job) use ($notification) {
            return (string) $job->notifId === (string) $notification->notif_id;
        });
    }

    public function test_send_scheduled_notification_job_handles_recurring_cycle(): void
    {
        $urgency = UrgencyLevel::first();
        if (!$urgency) {
            $urgency = UrgencyLevel::create([
                'urgency_key' => 'low',
                'urgency_label' => 'Low',
            ]);
        }

        $notification = Notification::create([
            'message'          => 'Unit Test Recurring Job Cycle',
            'sent_by'          => 1,
            'urgency_level_id' => $urgency->urgency_id,
            'channel'          => 'push',
            'status'           => 'scheduled',
            'target_filter'    => 'all',
            'is_recurring'     => true,
            'recurrence_end_at'=> now()->addDays(7),
        ]);

        // Mock NotificationService to avoid external API calls to OneSignal/TextBee
        $mockService = $this->createMock(NotificationService::class);
        $mockService->expects($this->once())
            ->method('resolveRecipients')
            ->willReturn([]);
        $mockService->expects($this->once())
            ->method('sendToChannels');

        $job = new SendScheduledNotification((string) $notification->notif_id);
        $job->handle($mockService);

        $notification->refresh();

        $this->assertNotNull($notification->last_sent_at);
        $this->assertEquals('scheduled', $notification->status);
    }
}
