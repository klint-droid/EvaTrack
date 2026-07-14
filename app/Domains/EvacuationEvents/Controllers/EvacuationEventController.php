<?php

namespace App\Domains\EvacuationEvents\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\EvacuationEvents\Requests\StoreDisasterEventRequest;
use App\Domains\EvacuationEvents\Requests\AssignCentersRequest;
use App\Domains\EvacuationEvents\DTOs\DisasterEventDTO;
use App\Domains\EvacuationEvents\DTOs\EventFilterDTO;
use App\Domains\EvacuationEvents\Actions\ListDisasterEventsAction;
use App\Domains\EvacuationEvents\Actions\GetHistoricalEventsAction;
use App\Domains\EvacuationEvents\Actions\GetActiveEventAction;
use App\Domains\EvacuationEvents\Actions\GetPublicActiveEventsAction;
use App\Domains\EvacuationEvents\Actions\CreateDisasterEventAction;
use App\Domains\EvacuationEvents\Actions\EndDisasterEventAction;
use App\Domains\EvacuationEvents\Actions\AssignCentersToEventAction;
use App\Domains\EvacuationEvents\Actions\UnassignCenterFromEventAction;
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
    public function index(ListDisasterEventsAction $action)
    {
        return response()->json([
            'data' => $action->execute()
        ]);
    }

    #[OA\Get(
        path: '/events/history',
        summary: 'Get historical disaster events with filters and pagination',
        security: [['bearerAuth' => []]],
        tags: ['Disaster Events']
    )]
    #[OA\Parameter(name: 'type_id', in: 'query', description: 'Filter by disaster type ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'start_date', in: 'query', description: 'Start date (Y-m-d)', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', in: 'query', description: 'End date (Y-m-d)', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function history(Request $request, GetHistoricalEventsAction $action)
    {
        $dto = EventFilterDTO::fromRequest($request);
        return response()->json($action->execute($dto));
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
    public function active(GetActiveEventAction $action)
    {
        $event = $action->execute();

        if (!$event) {
            return response()->json(['message' => 'No active event'], 404);
        }

        return response()->json(['data' => $event]);
    }

    public function activePublic(GetPublicActiveEventsAction $action)
    {
        return response()->json([
            'data' => $action->execute()
        ]);
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
    public function store(StoreDisasterEventRequest $request, CreateDisasterEventAction $action)
    {
        $dto = DisasterEventDTO::fromRequest($request);
        $event = $action->execute($dto);

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
    public function end($id, EndDisasterEventAction $action)
    {
        $event = DisasterEvent::where('event_id', $id)->firstOrFail();

        try {
            $endedEvent = $action->execute($event);
            return response()->json([
                'message' => 'Event ended and associated active evacuations checked out successfully.',
                'data'    => $endedEvent
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
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
    public function assignCenters(AssignCentersRequest $request, $id, AssignCentersToEventAction $action)
    {
        $event = DisasterEvent::where('event_id', $id)->firstOrFail();

        try {
            $action->execute($event, $request->center_id);
            return response()->json([
                'message' => 'Centers assigned successfully',
                'data'    => $event->load(['evacuationCenters', 'historicalCenters'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
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
    public function unassignCenter($centerId, UnassignCenterFromEventAction $action)
    {
        $action->execute($centerId);

        return response()->json([
            'message' => 'Center unassigned successfully',
        ]);
    }
}
