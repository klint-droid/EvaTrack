<?php

namespace App\Http\Controllers\API;

use App\Services\LiveAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function dashboard(Request $request)
    {
        $eventId = $request->query('event_id', 'all');
        $user = Auth::user();

        // Personnel are always scoped to their assigned center
        $centerId = null;
        if ($user->isEvacPersonnel() && $user->assigned_center_id) {
            $centerId = $user->assigned_center_id;
        }

        $data = $this->analyticsService->getDashboardAnalytics($eventId, $centerId);

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

}
