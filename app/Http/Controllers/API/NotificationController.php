<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\SendNotificationRequest;
use App\Models\Household;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationRecipient;
use App\Models\UrgencyLevel;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $service) {}

    // POST /api/notifications
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $user = Auth::user();

        $payload = array_merge($request->validated(), [
            'sent_by' => $user->user_id,
        ]);

        $notification = $this->service->dispatch($payload);

        return response()->json([
            'message'   => $notification->status === 'scheduled'
                ? 'Alert scheduled successfully.'
                : 'Alert sent successfully.',
            'notif_id'  => $notification->notif_id,
            'status'    => $notification->status,
            'recipient_count' => $notification->recipients()->count(),
        ], 201);
    }

    // GET /api/notifications
    public function index(): JsonResponse
    {
        $notifications = Notification::with(['sender', 'urgencyLevel'])
            ->withCount('recipients')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    // GET /api/notifications/{notification}
    public function show(string $id): JsonResponse
    {
        $notification = Notification::with([
            'sender',
            'urgencyLevel',
            'recipients.household',
            'logs',
        ])->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.'
            ], 404);
        }

        return response()->json($notification);
    }

    // GET /api/notifications/{notification}/logs
    public function logs(string $id): JsonResponse
    {
        // First check if notification exists
        $notification = Notification::find($id);
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.'
            ], 404);
        }

        $logs = NotificationLog::with('household')
            ->where('notification_id', $id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($logs);
    }

    // POST /api/notifications/{notification}/acknowledge
    public function acknowledge(Request $request, string $id): JsonResponse
    {
        // Validate household_id is provided
        $request->validate([
            'household_id' => 'required|exists:households,household_id'
        ]);

        // Check if notification exists
        $notification = Notification::find($id);
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.'
            ], 404);
        }

        $updated = NotificationRecipient::where('notification_id', $id)
            ->where('household_id', $request->input('household_id'))
            ->whereNull('acknowledged_at')
            ->update([
                'acknowledged_at' => now(),
                'read_at'         => now(),
            ]);

        if ($updated === 0) {
            return response()->json([
                'message' => 'No pending acknowledgement found for this household.',
                'acknowledged' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Notification acknowledged successfully.',
            'acknowledged' => true
        ]);
    }

    // DELETE /api/notifications/{notification} — cancel scheduled
    public function cancel(string $id): JsonResponse
    {
        $notification = Notification::where('notif_id', $id)
            ->where('status', 'scheduled')
            ->first();

        if (!$notification) {
            // Check if notification exists at all (for better error messaging)
            $exists = Notification::where('notif_id', $id)->exists();
            
            if (!$exists) {
                return response()->json([
                    'message' => 'Notification not found.'
                ], 404);
            }

            return response()->json([
                'message' => 'Only scheduled alerts can be cancelled.'
            ], 422);
        }

        $notification->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Scheduled alert cancelled successfully.',
            'notif_id' => $notification->notif_id
        ]);
    }

    // GET /api/notifications/preview — count recipients before sending
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'target_filter'        => 'required|in:all,evacuated,not_evacuated',
            'evacuation_center_id' => 'nullable|exists:evacuation_centers,evacuation_center_id',
            'evacuation_event_id'  => 'nullable|exists:evacuation_events,event_id',
        ]);

        $query = Household::on('mysql_v2');

        if ($request->target_filter === 'evacuated') {
            $query->whereHas('currentEvacuation');
        } elseif ($request->target_filter === 'not_evacuated') {
            $query->whereDoesntHave('currentEvacuation');
        }

        if ($request->evacuation_center_id) {
            $query->whereHas('currentEvacuation', fn($q) =>
                $q->where('center_id', $request->evacuation_center_id)
            );
        }

        if ($request->evacuation_event_id) {
            $query->whereHas('currentEvacuation', fn($q) =>
                $q->where('event_id', $request->evacuation_event_id)
            );
        }

        return response()->json([
            'recipient_count' => $query->count(),
        ]);
    }

    // GET /api/urgency-levels
    public function urgencyLevels(): JsonResponse
    {
        return response()->json([
            'data' => UrgencyLevel::all()
        ]);
    }
}