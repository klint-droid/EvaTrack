<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextBeeService
{
    protected string $apiKey;
    protected string $deviceId;
    protected string $baseUrl = 'https://api.textbee.dev/api/v1';

    public function __construct()
    {
        $this->apiKey   = config('services.textbee.api_key');
        $this->deviceId = config('services.textbee.device_id');
    }

    /**
     * Send an SMS to one or multiple recipients.
     *
     * @param  string|array  $recipients  E.164 format e.g. +639171234567
     * @param  string        $message
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    public function send(string|array $recipients, string $message): array
    {
        $recipients = (array) $recipients;

        try {
            $response = Http::withHeaders([
                'x-api-key'    => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/gateway/devices/{$this->deviceId}/send-sms", [
                'recipients' => $recipients,
                'message'    => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success'    => true,
                    'message_id' => $data['data']['id'] ?? null,
                    'error'      => null,
                ];
            }

            Log::error('[TextBee] Send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success'    => false,
                'message_id' => null,
                'error'      => $response->json('message') ?? "HTTP {$response->status()}",
            ];

        } catch (\Throwable $e) {
            Log::error('[TextBee] Exception', ['message' => $e->getMessage()]);

            return [
                'success'    => false,
                'message_id' => null,
                'error'      => $e->getMessage(),
            ];
        }
    }
}