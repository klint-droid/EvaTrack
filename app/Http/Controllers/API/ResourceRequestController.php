<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ResourceRequest;
use App\Models\ResourceRequestStatus;
use App\Models\EvacuationCenter;
use App\Models\UrgencyLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResourceRequestController extends Controller
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
    public function index(Request $request)
    {
        $user = Auth::user();
        $statusIds = $this->getStatusIds();
        $urgencyLevelIds = $this->getUrgencyLevelIds();

        $query = ResourceRequest::with($this->requestRelations());

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                return response()->json([
                    'message' => 'No evacuation center assigned.'
                ], 403);
            }

            $query->where('evacuation_center_id', $user->assigned_center_id);
        }

        if ($request->filled('center_id')) {
            if ($user->isEvacPersonnel() && $request->center_id !== $user->assigned_center_id) {
                return response()->json([
                    'message' => 'You are not assigned to this evacuation center.'
                ], 403);
            }

            $query->where('evacuation_center_id', $request->center_id);
        }

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

        return response()->json([
            'data' => $query->latest('created_at')->get(),
            'summary' => [
                'pending' => (clone $query)->where('status_id', $statusIds['pending'] ?? null)->count(),
                'acknowledged' => (clone $query)->where('status_id', $statusIds['acknowledged'] ?? null)->count(),
                'approved' => (clone $query)->where('status_id', $statusIds['approved'] ?? null)->count(),
                'rejected' => (clone $query)->where('status_id', $statusIds['rejected'] ?? null)->count(),
                'delivered_24h' => ResourceRequest::where('status_id', $statusIds['delivered'] ?? null)
                    ->where('updated_at', '>=', now()->subDay())
                    ->when($user->isEvacPersonnel(), function ($q) use ($user) {
                        $q->where('evacuation_center_id', $user->assigned_center_id);
                    })
                    ->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (
            !$user->isSuperAdmin() &&
            !$user->isEvacAdmin() &&
            !$user->isEvacPersonnel()
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'evacuation_center_id' => [
                'nullable',
                function($attribute, $value, $fail) {
                    if($value && !EvacuationCenter::where('evacuation_center_id', $value)->exists()) {
                        $fail('Evacuation center does not exist.');
                    }
                }
            ],
            'resource_type' => 'required|string|max:100',
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'urgency_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if(!UrgencyLevel::where('urgency_id', $value)->exists()) {
                        $fail('The selected urgency level is invalid.');
                    }
                }
            ],
        ]);

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                return response()->json([
                    'message' => 'No evacuation center assigned.'
                ], 403);
            }

            $centerId = $user->assigned_center_id;
        } else {
            if (!$request->filled('evacuation_center_id')) {
                return response()->json([
                    'message' => 'Evacuation center is required.'
                ], 422);
            }

            $centerId = $validated['evacuation_center_id'];
        }

        $pendingStatusId = ResourceRequestStatus::where('status_key', 'pending')->value('status_id');

        $requestRecord = ResourceRequest::create([
            'evacuation_center_id' => $centerId,
            'requested_by' => $user->user_id,
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

    public function show($id)
    {
        $user = Auth::user();

        $query = ResourceRequest::with($this->requestRelations())->where('request_id', $id);

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                return response()->json([
                    'message' => 'No evacuation center assigned.'
                ], 403);
            }

            $query->where('evacuation_center_id', $user->assigned_center_id);
        }

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

    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isEvacAdmin()) {
            return response()->json([
                'message' => 'Only admin users can update request status.'
            ], 403);
        }

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
            'handled_by' => $user->user_id,
        ]);

        return response()->json([
            'message' => 'Resource request status updated successfully.',
            'data' => $requestRecord->fresh($this->requestRelations()),
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $requestRecord = ResourceRequest::where('request_id', $id)->firstOrFail();

        $pendingStatusId = ResourceRequestStatus::where('status_key', 'pending')->value('status_id');

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

        if (!$user->isSuperAdmin() && !$user->isEvacAdmin() && !$user->isEvacPersonnel()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $requestRecord->delete();

        return response()->json([
            'message' => 'Resource request deleted successfully.'
        ]);
    }

    public function urgencyLevels()
    {
        return response()->json([
            'data' => UrgencyLevel::orderBy('urgency_label')->get()
        ]);
    }
}