<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\EvacuationCenter;
use App\Models\DisasterEvent;

class EvacuationEventController extends Controller
{
    // List all events
    public function index()
    {
        return response()->json([
            'data' => DisasterEvent::latest('started_at')->get()
        ]);
    }

    // Get active event (no ended_at yet)
    public function active()
    {
        $event = DisasterEvent::whereNull('ended_at')->latest('started_at')->first();

        if (!$event) {
            return response()->json(['message' => 'No active event'], 404);
        }

        return response()->json($event);
    }

    // Create a new event
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:50',
        ]);

        $event = DisasterEvent::create([
            'event_id'   => 'EVT-' . date('Y') . '-' . strtoupper(Str::random(6)),
            'name'       => $request->name,
            'type'       => $request->type,
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Event created successfully',
            'data'    => $event
        ], 201);
    }

    // End/close an event
    public function end($id)
    {
        $event = DisasterEvent::where('event_id', $id)->firstOrFail();

        if ($event->ended_at) {
            return response()->json(['message' => 'Event already ended'], 400);
        }

        EvacuationCenter::where('current_event_id', $id)->update(['current_event_id' => null]);

        $event->update(['ended_at' => now()]);

        return response()->json([
            'message' => 'Event ended successfully',
            'data'    => $event
        ]);
    }

    public function assignCenters(Request $request, $id){
        $request->validate([
            'center_id' => 'required|array',
            'center_id.*' => 'exists:evacuation_centers,evacuation_center_id'
        ]);

        $event = DisasterEvent::where('event_id', $id)->firstOrFail();

        if ($event->ended_at) {
            return response()->json(['message' => 'Event already ended'], 400);
        }

        EvacuationCenter::whereIn('evacuation_center_id', $request->center_id)
            ->update([
                'current_event_id' => $id
            ]);

        return response()->json([
            'message' => 'Centers assigned successfully',
            'data'    => $event
        ]);
    }

    public function unassignCenters($centerId){
        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

        $center->update([
            'current_event_id' => null
        ]);

        return response()->json([
            'message' => 'Center unassigned successfully',
            'data'    => $center
        ]);
    }
}