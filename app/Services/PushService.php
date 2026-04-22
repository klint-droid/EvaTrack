<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushService
{
    public function send($notification, $householdId)
    {
        // 🔥 Get player IDs
        $playerIds = DeviceToken::where('household_id', $householdId)
            ->pluck('player_id')
            ->toArray();

        if (empty($playerIds)) {
            Log::info('No player IDs found', [
                'household_id' => $householdId
            ]);
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . config('services.onesignal.api_key'),
                'Content-Type' => 'application/json'
            ])
            ->timeout(10)
            ->retry(3, 200)
            ->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => config('services.onesignal.app_id'),
                'include_player_ids' => $playerIds,
                'contents' => [
                    'en' => $notification->message
                ],
                'headings' => [
                    'en' => 'Evacuation Alert'
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Push failed', [
                    'response' => $response->json(),
                    'household_id' => $householdId
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Push exception', [
                'error' => $e->getMessage(),
                'household_id' => $householdId
            ]);
        }
    }
}