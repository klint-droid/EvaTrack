<?php

namespace App\Http\Controllers\API;

use App\Models\ResourceRequest;
use App\Models\ResourceRequestStatus;
use App\Models\EvacuationCenter;
use App\Models\UrgencyLevel;
use App\Http\Requests\StoreResourceRequestRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class ResourceRequestController extends BaseApiController
{
    private function getStatusIds(): array{
        return ResourceRequestStatus::pluck('status_id', 'status_key')->toArray();
    }

    private function getUrgencyLevelIds(): array{
        return UrgencyLevel::pluck('urgency_id', 'urgency_key')->toArray();
    }

    private function requestRelations(): array{
        return [
            'center',
            'requester',
            'handler',
            'urgencyLevel',
            'status',
        ];
    }

    #[OA\Get(
        path: '/resource-requests',
        summary: 'List resource requests',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by evacuation center ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'status', in: 'query', description: 'Filter by status key (pending, acknowledged, approved, rejected, delivered)', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'urgency_id', in: 'query', description: 'Filter by urgency key (low, medium, high, critical)', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Search query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limit number of results', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function index(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $user = Auth::user();
        $statusIds = $this->getStatusIds();
        $urgencyLevelIds = $this->getUrgencyLevelIds();

        $query = ResourceRequest::with($this->requestRelations());

        $query = $this->applyCenterFilter($query, $request);

        if ($request->filled('status')) {
            $statusId = $statusIds[$request->status] ?? null;
            
            if($statusId){
                $query->where('status_id', $statusId);
            }
        }

        if ($request->filled('urgency_id')) {
            $urgencyId = $urgencyLevelIds[$request->urgency_id] ?? null;
            
            if($urgencyId){
                $query->where('urgency_id', $urgencyId);
            }
        }

        if ($request->filled('q')) {
            $search = $request->q;

            $query->where(function ($q) use ($search) {
                $q->where('resource_type', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('request_id', 'LIKE', "%{$search}%");
            });
        }

        $summary = [
            'pending' => (clone $query)->where('status_id', $statusIds[ResourceRequestStatus::PENDING] ?? null)->count(),
            'acknowledged' => (clone $query)->where('status_id', $statusIds[ResourceRequestStatus::ACKNOWLEDGED] ?? null)->count(),
            'approved' => (clone $query)->where('status_id', $statusIds[ResourceRequestStatus::APPROVED] ?? null)->count(),
            'rejected' => (clone $query)->where('status_id', $statusIds[ResourceRequestStatus::REJECTED] ?? null)->count(),
            'delivered_24h' => ResourceRequest::where('status_id', $statusIds[ResourceRequestStatus::DELIVERED] ?? null)
                ->where('updated_at', '>=', now()->subDay())
                ->when($user->isEvacPersonnel(), function ($q) use ($user) {
                    $q->where('evacuation_center_id', $user->assigned_center_id);
                })
                ->count(),
        ];

        $limit = (int)$request->query('limit', 0);
        if ($limit > 0) {
            $query->limit($limit);
        }

        return response()->json([
            'data' => $query->latest('created_at')->get(),
            'summary' => $summary,
        ]);
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
    public function store(StoreResourceRequestRequest $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $validated = $request->validated();
        $centerId = $this->resolveUserCenterId($request);

        $pendingStatusId = ResourceRequestStatus::where('status_key', ResourceRequestStatus::PENDING)->value('status_id');

        $requestRecord = ResourceRequest::create([
            'evacuation_center_id' => $centerId,
            'requested_by' => Auth::id(),
            'handled_by' => null,
            'resource_type' => $validated['resource_type'],
            'quantity' => $validated['quantity'],
            'description' => $validated['description'] ?? null,
            'urgency_id' => $validated['urgency_id'],
            'status_id' => $pendingStatusId,
        ]);

        return response()->json([
            'message' => 'Resource request submitted successfully.',
            'data' => $requestRecord->load($this->requestRelations()),
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
    public function show($id)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $query = ResourceRequest::with($this->requestRelations())->where('request_id', $id);

        $query = $this->applyCenterFilter($query);

        $requestRecord = $query->first();

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
    public function updateStatus(Request $request, $id)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $validated = $request->validate([
            'status' => 'required|in:pending,acknowledged,approved,rejected,delivered',
        ]);

        $statusId = ResourceRequestStatus::where('status_key', $validated['status'])->value('status_id');

        if(!$statusId) {
            return response()->json([
                'message' => 'The selected status is invalid.'
            ], 422);
        }

        $requestRecord = ResourceRequest::where('request_id', $id)->firstOrFail();

        $requestRecord->update([
            'status_id' => $statusId, 
            'handled_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Resource request status updated successfully.',
            'data' => $requestRecord->fresh($this->requestRelations()),
        ]);
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
    public function destroy($id)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $user = Auth::user();
        $requestRecord = ResourceRequest::where('request_id', $id)->firstOrFail();

        $this->checkCenterOwnership($requestRecord->evacuation_center_id);

        $pendingStatusId = ResourceRequestStatus::where('status_key', ResourceRequestStatus::PENDING)->value('status_id');

        if ($user->isEvacPersonnel()) {
            if ($requestRecord->requested_by !== $user->user_id) {
                return response()->json([
                    'message' => 'You can only delete your own request.'
                ], 403);
            }

            if ($requestRecord->status_id !== $pendingStatusId) {
                return response()->json([
                    'message' => 'Only pending requests can be deleted.'
                ], 400);
            }
        }

        $requestRecord->delete();

        return response()->json([
            'message' => 'Resource request deleted successfully.'
        ]);
    }

    #[OA\Get(
        path: '/urgency-levels',
        summary: 'Get all urgency levels',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function urgencyLevels()
    {
        return response()->json([
            'data' => UrgencyLevel::orderBy('urgency_label')->get()
        ]);
    }
}