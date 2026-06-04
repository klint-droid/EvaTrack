<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\EvacuationCenter;
use App\Models\DisasterEvent;
use App\Models\DisasterType;
use App\Models\SeverityLevel;
use App\Models\EvacuationRecord;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class EvacuationEventController extends Controller
{
    #[OA\Get(
        path: '/events',
        summary: 'List all disaster events',
        security: [['bearerAuth' => []]],
        tags: ['Disaster Events']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index()
    {
        return response()->json([
            'data' => DisasterEvent::with(['primaryType', 'severity', 'evacuationCenters'])
                ->latest('started_at')
                ->get()
        ]);
    }

    #[OA\Get(
        path: '/events/active',
        summary: 'Get active disaster event',
        security: [['bearerAuth' => []]],
        tags: ['Disaster Events']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'No active event')]
    public function active()
    {
        $event = DisasterEvent::with(['primaryType', 'types', 'evacuationCenters'])
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if (!$event) {
            return response()->json(['message' => 'No active event'], 404);
        }

        return response()->json(['data' => $event]);
    }

    #[OA\Post(
        path: '/events',
        summary: 'Create a new disaster event',
        security: [['bearerAuth' => []]],
        tags: ['Disaster Events']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'type_id', 'severity_id'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'type_id', type: 'integer'),
                new OA\Property(property: 'severity_id', type: 'integer'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type_id' => [
                'required',
                function($attribute, $value, $fail) {
                    $existingType = DisasterType::where('type_id', $value)->exists();

                    if(!$existingType) {
                        $fail('The selected type is invalid');
                    }
                },
            ],  
            'severity_id' => [
                'required',
                function($attribute, $value, $fail) {
                    $existingSeverity = SeverityLevel::where('severity_id', $value)->exists();

                    if(!$existingSeverity) {
                        $fail('The selected severity is invalid');
                    }
                },
            ]
        ]);

        $event = DisasterEvent::create([
            'name'       => $request->name,
            'type_id'       => $request->type_id,
            'severity_level_id'   => $request->severity_id,
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Event created successfully',
            'data'    => $event->load(['primaryType', 'severity'])
        ], 201);
    }

    #[OA\Patch(
        path: '/events/{id}/end',
        summary: 'End a disaster event',
        description: 'Marks event as ended, unassigns centers, and checkouts active evacuations.',
        security: [['bearerAuth' => []]],
        tags: ['Disaster Events']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Event ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Event already ended')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Event not found')]
    public function end($id)
    {
        $event = DisasterEvent::where('event_id', $id)->firstOrFail();

        if ($event->ended_at) {
            return response()->json(['message' => 'Event already ended'], 400);
        }

        return DB::connection('mysql_v2')->transaction(function () use ($event, $id) {
            EvacuationCenter::where('current_event_id', $id)->update(['current_event_id' => null]);

            EvacuationRecord::where('event_id', $id)
                ->where('household_status_id', 2)
                ->update([
                    'household_status_id' => 6,
                    'updated_at' => now()
                ]);

            $event->update(['ended_at' => now()]);

            return response()->json([
                'message' => 'Event ended and associated active evacuations checked out successfully.',
                'data'    => $event
            ]);
        });
    }

    #[OA\Patch(
        path: '/events/{id}/assign-centers',
        summary: 'Assign evacuation centers to a disaster event',
        security: [['bearerAuth' => []]],
        tags: ['Disaster Events']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Event ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['center_id'],
            properties: [
                new OA\Property(property: 'center_id', type: 'array', items: new OA\Items(type: 'integer')),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Event already ended')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Event not found')]
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
            'data'    => $event->load('evacuationCenters')
        ]);
    }

    #[OA\Patch(
        path: '/centers/{centerId}/unassign',
        summary: 'Unassign center from event',
        security: [['bearerAuth' => []]],
        tags: ['Disaster Events']
    )]
    #[OA\Parameter(name: 'centerId', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Center not found')]
    public function unassignCenter($centerId){
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