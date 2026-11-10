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
            $postData = [
                'app_id'   => $this->appId,
                'headings' => ['en' => $title],
                'contents' => ['en' => $body],
                'data'     => empty($data) ? new \stdClass() : $data,
            ];

            if (!empty($data['url'])) {
                $postData['url'] = $data['url'];
            }

            // Target modern OneSignal User IDs and Subscription IDs
            $postData['include_aliases'] = [
                'onesignal_id' => array_values($playerIds),
            ];
            $postData['target_channel'] = 'push';
            $postData['include_subscription_ids'] = array_values($playerIds);

            $response = Http::withHeaders([
                'Authorization' => "Basic {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])->post("{$this->baseUrl}/notifications", $postData);

            // If include_subscription_ids or include_aliases failed, fallback to legacy include_player_ids
            if (!$response->successful() && str_contains($response->body(), 'include_subscription_ids')) {
                unset($postData['include_aliases'], $postData['target_channel'], $postData['include_subscription_ids']);
                $postData['include_player_ids'] = array_values($playerIds);
                $response = Http::withHeaders([
                    'Authorization' => "Basic {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ])->post("{$this->baseUrl}/notifications", $postData);
            }

            if ($response->successful()) {
                $notificationId = $response->json('id');
                $errors = $response->json('errors');

                if (empty($notificationId)) {
                    $errorMsg = is_array($errors) ? json_encode($errors) : ($errors ?? 'Empty notification ID returned');
                    Log::warning('[OneSignal] Notification rejected/unsubscribed', [
                        'status' => $response->status(),
                        'errors' => $errors,
                        'body'   => $response->body(),
                    ]);

                    return [
                        'success'         => false,
                        'notification_id' => null,
                        'error'           => $errorMsg,
                    ];
                }

                if (!empty($errors)) {
                    Log::info('[OneSignal] Notification sent with partial errors/warnings', [
                        'notification_id' => $notificationId,
                        'errors'          => $errors,
                    ]);
                }

                return [
                    'success'         => true,
                    'notification_id' => $notificationId,
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