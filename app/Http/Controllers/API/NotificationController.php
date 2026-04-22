<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Household;
use App\Jobs\SendNotificationJob;
use App\Models\User;

class NotificationController extends Controller
{
    public function send(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'message' => 'required|string',
            'urgency_level_id' => 'required|string',
            'evacuation_event_id' => 'nullable|string',
            'evacuation_center_id' => 'nullable|string',
        ]);

        $notification = Notification::create([
            'message' => $request->message,
            'sent_by' => $user->user_id,
            'urgency_level_id' => $request->urgency_level_id,
            'evacuation_event_id' => $request->evacuation_event_id,
            'evacuation_center_id' => $request->evacuation_center_id,
            'scheduled_at' => null,
            'created_at' => now(),
        ]);

        $notifId = $notification->notif_id;

        $households = Household::select('household_id', 'contact_number')->get();

        foreach ($households as $household) {
            if (!$household->contact_number) {
                continue;
            }

            NotificationRecipient::create([
                'notification_id' => $notifId,
                'household_id' => $household->household_id,
            ]);
        }

        SendNotificationJob::dispatch($notifId);

        return response()->json([
            'message' => 'Notification queued successfully',
            'notif_id' => $notifId
        ]);
    }
}