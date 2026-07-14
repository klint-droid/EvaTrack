<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Services\LiveAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(LiveAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD (new)
    |--------------------------------------------------------------------------
    */

    #[OA\Get(
        path: '/analytics/dashboard',
        summary: 'Get dashboard analytics data',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'event_id', in: 'query', description: 'Filter by event ID or "all"', required: false, schema: new OA\Schema(type: 'string', default: 'all'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by center ID or "all"', required: false, schema: new OA\Schema(type: 'string', default: 'all'))]
    #[OA\Parameter(name: 'start_date', in: 'query', description: 'Filter from date', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', in: 'query', description: 'Filter to date', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function dashboard(Request $request)
    {
        $eventId = $request->query('event_id', 'all');
        $user = Auth::user();

        // Personnel are always scoped to their assigned center
        $centerId = null;
        if ($user->isEvacPersonnel() && $user->assigned_center_id) {
            $centerId = $user->assigned_center_id;
        } else {
            $centerId = $request->query('center_id');
            if ($centerId === 'all') {
                $centerId = null;
            }
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $data = $this->analyticsService->getDashboardAnalytics($eventId, $centerId, $startDate, $endDate);

        return response()->json([
            'success'  => true,
            'event_id' => $eventId,
            'data'     => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | EVENTS LIST (for dropdown selector)
    |--------------------------------------------------------------------------
    */

    #[OA\Get(
        path: '/analytics/events-list',
        summary: 'Get list of disaster events for dropdowns',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function eventsList()
    {
        $events = $this->analyticsService->getEventsList();

        return response()->json([
            'success' => true,
            'events'  => $events,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LEGACY ENDPOINTS (preserved)
    |--------------------------------------------------------------------------
    */

    #[OA\Get(
        path: '/analytics/event/{eventId}',
        summary: 'Get analytics for a specific event (legacy)',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'eventId', in: 'path', description: 'Event ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Event not found')]
    public function eventAnalytics($eventId)
    {
        $data = $this->analyticsService->getEventAnalytics($eventId);
        
        return response()->json([
            'success' => true,
            'scope' => 'event',
            'event_id' => $eventId,
            'analytics' => $data
        ]);
    }
    
    #[OA\Get(
        path: '/analytics/event/{eventId}/center/{centerId}',
        summary: 'Get analytics for a specific event at a center (legacy)',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'eventId', in: 'path', description: 'Event ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'centerId', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Event or Center not found')]
    public function centerAnalytics($eventId, $centerId)
    {
        $data = $this->analyticsService->getCenterAnalytics($eventId, $centerId);
        
        return response()->json([
            'success' => true,
            'scope' => 'center',
            'event_id' => $eventId,
            'center_id' => $centerId,
            'analytics' => $data
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LAST UPDATED (Job Log)
    |--------------------------------------------------------------------------
    */

    #[OA\Get(
        path: '/analytics/last-updated',
        summary: 'Get the timestamp of the last successful analytics job run',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function lastUpdated()
    {
        $latestRecord = \App\Domains\Evacuations\Models\EvacuationRecord::max('updated_at');
        $latestEvent = \App\Domains\EvacuationEvents\Models\DisasterEvent::max('updated_at');
        $latestRequest = \App\Domains\ResourceRequests\Models\ResourceRequest::max('updated_at');
        $latestIssue = \App\Domains\CenterIssueReports\Models\CenterIssueReport::max('updated_at');

        $dates = array_filter([$latestRecord, $latestEvent, $latestRequest, $latestIssue]);
        
        $lastUpdated = empty($dates) ? null : max($dates);

        return response()->json([
            'success' => true,
            'last_updated' => $lastUpdated
        ]);
    }
}
