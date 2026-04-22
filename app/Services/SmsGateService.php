<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SmsGateService
{
    /**
     * Create a new class instance.
     */
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('SMSGATE_BASE_URL');
    }

    private function getToken()
    {
        if (Cache::has('smsgate_token')) {
            return Cache::get('smsgate_token');
        }

        $response = Http::withBasicAuth(
                env('SMSGATE_USERNAME'),
                env('SMSGATE_PASSWORD')
            )
            ->asJson()
            ->post($this->baseUrl . '/auth/token', [
                'scopes' => [
                    'devices:list',
                    'messages:send',
                    'messages:list',
                    'messages:read',
                    'webhooks:write',
                    'webhooks:read',
                    'webhooks:list'
                ],
                'ttl' => 3600,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Auth failed: ' . $response->body());
        }

        $data = $response->json();

        $token = $data['access_token'];

        Cache::put('smsgate_token', $token, now()->addMinutes(55));

        return $token;
    }

     private function request($method, $endpoint, $data = [])
    {
        $token = $this->getToken();

        $response = Http::withToken($token)
            ->$method($this->baseUrl . $endpoint, $data);

        if ($response->status() === 401) {
            Cache::forget('smsgate_token');

            $token = $this->getToken();

            $response = Http::withToken($token)
                ->$method($this->baseUrl . $endpoint, $data);
        }

        return $response;
    }

    public function getDevices()
    {
        return $this->request('get', '/devices')->json();
    }
    private function formatNumber($number)
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if (str_starts_with($number, '09')) {
            return '63' . substr($number, 1);
        }

        if (str_starts_with($number, '639')) {
            return $number;
        }

        return $number;
    }

    public function sendSMS($number, $message, $notificationId, $householdId)
    {
        $formattedNumber = $this->formatNumber($number);

        $response = $this->request(
            'post',
            '/messages?skipPhoneValidation=true&deviceActiveWithin=0',
            [
                'deviceId' => env('SMSGATE_DEVICE_ID'),
                'phoneNumbers' => [$formattedNumber],
                'message' => $message,
                'simNumber' => 2,
            ]
        );

        $data = $response->json();

        $messageId = $data['result']['messageId']
            ?? $data['messageId']
            ?? null;

        $isSuccess = $response->successful() && $messageId;

        if (!$isSuccess) {
            \Log::error('SMS sending failed', [
                'response' => $data,
                'number' => $formattedNumber,
            ]);
        }

        \App\Models\NotificationLog::create([
            'notification_id' => $notificationId,
            'household_id' => $householdId,
            'channel' => 'sms',
            'status' => $isSuccess ? 'sent' : 'failed',
            'sent_at' => now(),
            'retry_count' => 0,
            'external_message_id' => $messageId,
        ]);

        return $messageId;
    }

    public function getMessageStatus($messageId)
    {
        return $this->request('get', "/messages/{$messageId}")
            ->json();
    }

    public function registerWebhook()
    {
        $events = ['sms:sent', 'sms:delivered', 'sms:failed'];

        $results = [];

        foreach ($events as $event) {
            $results[] = $this->request('post', '/webhooks', [
                'deviceId' => env('SMSGATE_DEVICE_ID'),
                'event' => $event,
                'id' => 'evatrack-' . str_replace(':', '-', $event),
                'url' => env('APP_URL') . '/api/sms/webhook'
            ])->json();
        }

        return $results;
    }

    public function listWebhooks()
{
    return $this->request('get', '/webhooks')->json();
}
}
