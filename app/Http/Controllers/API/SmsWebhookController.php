<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\AlertLog;

class SmsWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();

        \Log::info('SMSGate Webhook:', $data);

        $event = $request->input('event');

        $payload = $request->input('payload', []);

        $messageId = $payload['messageId'] ?? null;
        $phone = $payload['phoneNumber'] ?? null;
        $status = str_replace('sms:', '', $event);

        if (!$messageId || !$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payload',
                'received' => $data
            ], 400);
        }

        $log = AlertLog::where('message_id', $messageId)->first();

        if ($log) {
            $log->update([
                'delivery_status' => $status
            ]);

            if ($event === 'sms:delivered') {
                $log->update([
                    'is_success' => true,
                    'delivered_at' => now()
                ]);
            }

            if ($event === 'sms:failed') {
                $log->update([
                    'is_failed' => true
                ]);
            }

        } else {
            \Log::warning('AlertLog not found', [
                'message_id' => $messageId
            ]);
        }

        return response()->json([
            'success' => true,
            'event' => $event,
            'message_id' => $messageId,
            'phone' => $phone,
            'status' => $status
        ]);
    }
}