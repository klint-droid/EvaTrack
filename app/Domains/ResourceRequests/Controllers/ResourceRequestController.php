<?php

namespace App\Domains\ResourceRequests\Controllers;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Domains\ResourceRequests\Requests\StoreResourceRequest;
use App\Domains\ResourceRequests\Requests\UpdateResourceRequestStatusRequest;
use App\Domains\ResourceRequests\DTOs\ResourceRequestDTO;
use App\Domains\ResourceRequests\DTOs\ResourceRequestFilterDTO;
use App\Domains\ResourceRequests\Actions\ListResourceRequestsAction;
use App\Domains\ResourceRequests\Actions\CreateResourceRequestAction;
use App\Domains\ResourceRequests\Actions\GetResourceRequestAction;
use App\Domains\ResourceRequests\Actions\UpdateResourceRequestStatusAction;
use App\Domains\ResourceRequests\Actions\DeleteResourceRequestAction;
use App\Domains\ResourceRequests\Actions\ListUrgencyLevelsAction;
use OpenApi\Attributes as OA;
use Exception;

class ResourceRequestController extends BaseApiController
{
    #[OA\Get(
        path: '/resource-requests',
        summary: 'List resource requests',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by evacuation center ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'status', in: 'query', description: 'Filter by status key (pending, acknowledged, approved, rejected, delivered)', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'urgency_id', in: 'query', description: 'Filter by urgency ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Search query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limit number of results', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function index(Request $request, ListResourceRequestsAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $user = Auth::user();
        $enforcedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;
        
        $filter = ResourceRequestFilterDTO::fromRequest($request);

        $result = $action->execute($filter, $enforcedCenterId);

        return response()->json($result);
    }

    #[OA\Post(
        path: '/resource-requests',
        summary: 'Submit a new resource request',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['resource_type', 'quantity', 'urgency_id'],
            properties: [
                new OA\Property(property: 'evacuation_center_id', type: 'integer', nullable: true),
                new OA\Property(property: 'resource_type', type: 'string'),
                new OA\Property(property: 'quantity', type: 'integer'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'urgency_id', type: 'integer'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 422, description: 'Validation errors')]
    public function store(StoreResourceRequest $request, CreateResourceRequestAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $user = Auth::user();
        $enforcedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;

        $dto = ResourceRequestDTO::fromRequest($request);

        $requestRecord = $action->execute($dto, $user->user_id, $enforcedCenterId);

        return response()->json([
            'message' => 'Resource request submitted successfully.',
            'data'    => $requestRecord,
        ], 201);
    }

    #[OA\Get(
        path: '/resource-requests/{id}',
        summary: 'Get resource request details',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Request ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Request not found')]
    public function show($id, GetResourceRequestAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $user = Auth::user();
        $enforcedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;

        $requestRecord = $action->execute($id, $enforcedCenterId);

        if (!$requestRecord) {
            return response()->json([
                'message' => 'Resource request not found or unauthorized.'
            ], 404);
        }

        return response()->json([
            'data' => $requestRecord,
        ]);
    }

    #[OA\Patch(
        path: '/resource-requests/{id}/status',
        summary: 'Update resource request status',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Request ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(property: 'status', type: 'string', enum: ['pending', 'acknowledged', 'approved', 'rejected', 'delivered']),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Request not found')]
    #[OA\Response(response: 422, description: 'Invalid status')]
    public function updateStatus(UpdateResourceRequestStatusRequest $request, $id, UpdateResourceRequestStatusAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        try {
            $requestRecord = $action->execute($id, $request->validated('status'), Auth::id());

            return response()->json([
                'message' => 'Resource request status updated successfully.',
                'data'    => $requestRecord,
            ]);
        } catch (Exception $e) {
            $status = $e->getCode() === 404 ? 404 : 422;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    #[OA\Delete(
        path: '/resource-requests/{id}',
        summary: 'Delete resource request',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Request ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 400, description: 'Only pending requests can be deleted')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Request not found')]
    public function destroy($id, DeleteResourceRequestAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $user = Auth::user();
        $enforcedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;

        try {
            $action->execute($id, $user, $enforcedCenterId);
            return response()->json([
                'message' => 'Resource request deleted successfully.'
            ]);
        } catch (Exception $e) {
            $status = in_array($e->getCode(), [400, 403, 404]) ? $e->getCode() : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    #[OA\Get(
        path: '/urgency-levels',
        summary: 'Get all urgency levels',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function urgencyLevels(ListUrgencyLevelsAction $action)
    {
        return response()->json([
            'data' => $action->execute()
        ]);
    }
}
