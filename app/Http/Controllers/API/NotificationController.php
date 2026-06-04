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
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $service) {}

    #[OA\Post(
        path: '/notifications',
        summary: 'Send or schedule notification alert',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title', 'message', 'urgency_id', 'target_filter'],
            properties: [
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'urgency_id', type: 'integer'),
                new OA\Property(property: 'target_filter', type: 'string', enum: ['all', 'evacuated', 'not_evacuated']),
                new OA\Property(property: 'evacuation_center_id', type: 'integer', nullable: true),
                new OA\Property(property: 'evacuation_event_id', type: 'integer', nullable: true),
                new OA\Property(property: 'channels', type: 'array', items: new OA\Items(type: 'string', enum: ['sms', 'in_app', 'web_push'])),
                new OA\Property(property: 'scheduled_at', type: 'string', format: 'date-time', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Sent or scheduled successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation errors')]
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

    #[OA\Get(
        path: '/notifications',
        summary: 'List sent/scheduled notifications',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(): JsonResponse
    {
        $notifications = Notification::with(['sender', 'urgencyLevel'])
            ->withCount('recipients')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    #[OA\Get(
        path: '/notifications/{notification}',
        summary: 'Get notification details',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    #[OA\Parameter(name: 'notification', in: 'path', description: 'Notification ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Notification not found')]
    public function show(string $id): JsonResponse
    {
        $notification = Notification::with([
            'sender',
            'urgencyLevel',
            'recipients.household',
            'logs.channel',
            'logs.status',
        ])->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.'
            ], 404);
        }

        return response()->json($notification);
    }

    #[OA\Get(
        path: '/notifications/{notification}/logs',
        summary: 'Get logs/delivery statuses of a notification',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    #[OA\Parameter(name: 'notification', in: 'path', description: 'Notification ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Notification not found')]
    public function logs(string $id): JsonResponse
    {
        // First check if notification exists
        $notification = Notification::find($id);
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.'
            ], 404);
        }

        $logs = NotificationLog::with(['household', 'channel', 'status'])
            ->where('notification_id', $id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($logs);
    }

    #[OA\Post(
        path: '/notifications/{notification}/acknowledge',
        summary: 'Acknowledge receipt of a notification',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    #[OA\Parameter(name: 'notification', in: 'path', description: 'Notification ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['household_id'],
            properties: [
                new OA\Property(property: 'household_id', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Acknowledged successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Notification or recipient household not found')]
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

    #[OA\Delete(
        path: '/notifications/{notification}',
        summary: 'Cancel scheduled notification',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    #[OA\Parameter(name: 'notification', in: 'path', description: 'Notification ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Cancelled successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Notification not found')]
    #[OA\Response(response: 422, description: 'Only scheduled notifications can be cancelled')]
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

    #[OA\Get(
        path: '/notifications/preview',
        summary: 'Count potential recipients for a filter',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    #[OA\Parameter(name: 'target_filter', in: 'query', description: 'Filter target (all, evacuated, not_evacuated)', required: true, schema: new OA\Schema(type: 'string', enum: ['all', 'evacuated', 'not_evacuated']))]
    #[OA\Parameter(name: 'evacuation_center_id', in: 'query', description: 'Filter by evacuation center ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'evacuation_event_id', in: 'query', description: 'Filter by evacuation event ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation errors')]
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

    #[OA\Get(
        path: '/notifications/urgency-levels',
        summary: 'Get all urgency levels for alerts',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function urgencyLevels(): JsonResponse
    {
        return response()->json([
            'data' => UrgencyLevel::all()
        ]);
    }
}