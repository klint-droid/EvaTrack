<?php

namespace App\Http\Controllers\API;

use App\Services\LiveAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(LiveAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

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
