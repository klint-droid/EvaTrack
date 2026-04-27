<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CenterIssueReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CenterIssueReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = CenterIssueReport::with([
            'center.address',
            'reportedBy',
            'handledBy',
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
            if (
                $user->isEvacPersonnel() &&
                $request->center_id !== $user->assigned_center_id
            ) {
                return response()->json([
                    'message' => 'You are not assigned to this evacuation center.'
                ], 403);
            }

            $query->where('evacuation_center_id', $request->center_id);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $search = $request->q;

            $query->where(function ($q) use ($search) {
                $q->where('report_id', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->latest('created_at')->get(),
            'summary' => [
                'open' => (clone $query)->where('status', 'open')->count(),
                'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
                'resolved' => (clone $query)->where('status', 'resolved')->count(),
                'critical' => (clone $query)->where('severity', 'critical')->count(),
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

            'category' => 'required|in:incident,facility_issue,health_issue,safety_issue,other',
            'title' => 'required|string|max:150',
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
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

        $report = CenterIssueReport::create([
            'evacuation_center_id' => $centerId,
            'reported_by' => $user->user_id,
            'handled_by' => null,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Center issue report submitted successfully.',
            'data' => $report->load([
                'center.address',
                'reportedBy',
                'handledBy',
            ]),
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();

        $query = CenterIssueReport::with([
            'center.address',
            'reportedBy',
            'handledBy',
        ])->where('report_id', $id);

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                return response()->json([
                    'message' => 'No evacuation center assigned.'
                ], 403);
            }

            $query->where('evacuation_center_id', $user->assigned_center_id);
        }

        $report = $query->first();

        if (!$report) {
            return response()->json([
                'message' => 'Center issue report not found or unauthorized.'
            ], 404);
        }

        return response()->json([
            'data' => $report,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $report = CenterIssueReport::where('report_id', $id)->firstOrFail();

        if ($user->isEvacPersonnel()) {
            if ($report->reported_by !== $user->user_id) {
                return response()->json([
                    'message' => 'You can only update your own report.'
                ], 403);
            }

            if (!in_array($report->status, ['open'])) {
                return response()->json([
                    'message' => 'Only open reports can be edited.'
                ], 400);
            }
        }

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
            'category' => 'sometimes|in:incident,facility_issue,health_issue,safety_issue,other',
            'title' => 'sometimes|string|max:150',
            'description' => 'sometimes|string',
            'severity' => 'sometimes|in:low,medium,high,critical',
        ]);

        $report->update($validated);

        return response()->json([
            'message' => 'Center issue report updated successfully.',
            'data' => $report->fresh([
                'center.address',
                'reportedBy',
                'handledBy',
            ]),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isEvacAdmin()) {
            return response()->json([
                'message' => 'Only admin users can update report status.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $report = CenterIssueReport::where('report_id', $id)->firstOrFail();

        $report->update([
            'status' => $validated['status'],
            'handled_by' => $user->user_id,
        ]);

        return response()->json([
            'message' => 'Center issue report status updated successfully.',
            'data' => $report->fresh([
                'center.address',
                'reportedBy',
                'handledBy',
            ]),
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $report = CenterIssueReport::where('report_id', $id)->firstOrFail();

        if ($user->isEvacPersonnel()) {
            if ($report->reported_by !== $user->user_id) {
                return response()->json([
                    'message' => 'You can only delete your own report.'
                ], 403);
            }

            if ($report->status !== 'open') {
                return response()->json([
                    'message' => 'Only open reports can be deleted.'
                ], 400);
            }
        }

        if (
            !$user->isSuperAdmin() &&
            !$user->isEvacAdmin() &&
            !$user->isEvacPersonnel()
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $report->delete();

        return response()->json([
            'message' => 'Center issue report deleted successfully.'
        ]);
    }
}