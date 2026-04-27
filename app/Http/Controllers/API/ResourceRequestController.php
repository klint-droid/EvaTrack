<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ResourceRequest;
use App\Models\UrgencyLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResourceRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = ResourceRequest::with([
            'center.address',
            'requestedBy',
            'handledBy',
            'urgency',
        ]);

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
            $query->where('status', $request->status);
        }

        if ($request->filled('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        if ($request->filled('urgency_id')) {
            $query->where('urgency_id', $request->urgency_id);
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
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'acknowledged' => (clone $query)->where('status', 'acknowledged')->count(),
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
                'delivered_24h' => ResourceRequest::where('status', 'delivered')
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
            'evacuation_center_id' => 'nullable|exists:evacuation_centers,evacuation_center_id',
            'request_type' => 'required|in:resource,personnel',
            'resource_type' => 'required|string|max:100',
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'urgency_id' => 'required|exists:urgency_levels,urgency_id',
            'target_agency' => 'nullable|string|max:100',
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

        $requestRecord = ResourceRequest::create([
            'evacuation_center_id' => $centerId,
            'requested_by' => $user->user_id,
            'handled_by' => null,
            'request_type' => $validated['request_type'],
            'resource_type' => $validated['resource_type'],
            'quantity' => $validated['quantity'],
            'description' => $validated['description'] ?? null,
            'urgency_id' => $validated['urgency_id'],
            'status' => 'pending',
            'target_agency' => $validated['target_agency'] ?? 'ResQperation',
        ]);

        return response()->json([
            'message' => 'Resource request submitted successfully.',
            'data' => $requestRecord->load([
                'center.address',
                'requestedBy',
                'handledBy',
                'urgency',
            ]),
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();

        $query = ResourceRequest::with([
            'center.address',
            'requestedBy',
            'handledBy',
            'urgency',
        ])->where('request_id', $id);

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

        $requestRecord = ResourceRequest::where('request_id', $id)->firstOrFail();

        $requestRecord->update([
            'status' => $validated['status'],
            'handled_by' => $user->user_id,
        ]);

        return response()->json([
            'message' => 'Resource request status updated successfully.',
            'data' => $requestRecord->fresh([
                'center.address',
                'requestedBy',
                'handledBy',
                'urgency',
            ]),
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $requestRecord = ResourceRequest::where('request_id', $id)->firstOrFail();

        if ($user->isEvacPersonnel()) {
            if ($requestRecord->requested_by !== $user->user_id) {
                return response()->json([
                    'message' => 'You can only delete your own request.'
                ], 403);
            }

            if ($requestRecord->status !== 'pending') {
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