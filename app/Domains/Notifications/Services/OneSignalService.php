<?php

namespace App\Domains\Notifications\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    protected string $appId;
    protected string $apiKey;
    protected string $baseUrl = 'https://onesignal.com/api/v1';

    public function __construct()
    {
        $this->appId  = config('services.onesignal.app_id');
        $this->apiKey = config('services.onesignal.api_key');
    }

    /**
     * Send push notification to specific player IDs.
     *
     * @param  array   $playerIds  OneSignal subscription IDs
     * @param  string  $title
     * @param  string  $body
     * @param  array   $data       Extra payload attached to the notification
     * @return array{success: bool, notification_id: string|null, error: string|null}
     */
    public function sendToPlayers(array $playerIds, string $title, string $body, array $data = []): array
    {
        if (empty($playerIds)) {
            return [
                'success'         => false,
                'notification_id' => null,
                'error'           => 'No player IDs provided',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])->post("{$this->baseUrl}/notifications", [
                'app_id'             => $this->appId,
                'include_player_ids' => $playerIds,
                'headings'           => ['en' => $title],
                'contents'           => ['en' => $body],
                'data'               => empty($data) ? new \stdClass() : $data,
            ]);

            if ($response->successful()) {
                return [
                    'success'         => true,
                    'notification_id' => $response->json('id'),
                    'error'           => null,
                ];
            }

            Log::error('[OneSignal] Send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success'         => false,
                'notification_id' => null,
                'error'           => $response->json('errors.0') ?? "HTTP {$response->status()}",
            ];

        } catch (\Throwable $e) {
            Log::error('[OneSignal] Exception', ['message' => $e->getMessage()]);

            return [
                'success'         => false,
                'notification_id' => null,
                'error'           => $e->getMessage(),
            ];
        }
    }
}