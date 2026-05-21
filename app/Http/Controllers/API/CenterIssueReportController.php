<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CenterIssueReport;
use App\Models\CenterIssueReportStatus;
use App\Models\CenterIssueCategory;
use App\Models\SeverityLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CenterIssueReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = CenterIssueReport::with([
            'center',
            'reporter',
            'handler',
            'category',
            'severityLevel',
            'status',
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
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('category_key', $request->category);
            });
        }

        if ($request->filled('severity')) {
            $query->whereHas('severityLevel', function ($q) use ($request) {
                $q->where('severity_key', $request->severity);
            });
        }

        if ($request->filled('status')) {
            $query->whereHas('status', function ($q) use ($request) {
                $q->where('status_key', $request->status);
            });
        }

        if ($request->filled('q')) {
            $search = $request->q;

            $query->where(function ($q) use ($search) {
                $q->where('report_id', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Build summary counts using the lookup table relationships
        $openStatusId = CenterIssueReportStatus::where('status_key', 'open')->value('status_id');
        $inProgressStatusId = CenterIssueReportStatus::where('status_key', 'in_progress')->value('status_id');
        $resolvedStatusId = CenterIssueReportStatus::where('status_key', 'resolved')->value('status_id');
        $criticalSeverityId = SeverityLevel::where('severity_key', 'critical')->value('severity_id');

        return response()->json([
            'data' => $query->latest('created_at')->get()->map(function ($report) {
                return $this->formatReport($report);
            }),
            'summary' => [
                'open' => $openStatusId ? (clone $query)->where('status_id', $openStatusId)->count() : 0,
                'in_progress' => $inProgressStatusId ? (clone $query)->where('status_id', $inProgressStatusId)->count() : 0,
                'resolved' => $resolvedStatusId ? (clone $query)->where('status_id', $resolvedStatusId)->count() : 0,
                'critical' => $criticalSeverityId ? (clone $query)->where('severity_id', $criticalSeverityId)->count() : 0,
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

        // Resolve string keys to foreign key IDs
        $categoryId = CenterIssueCategory::where('category_key', $validated['category'])->value('category_id');
        $severityId = SeverityLevel::where('severity_key', $validated['severity'])->value('severity_id');
        $statusId = CenterIssueReportStatus::where('status_key', 'open')->value('status_id');

        $report = CenterIssueReport::create([
            'evacuation_center_id' => $centerId,
            'reported_by' => $user->user_id,
            'handled_by' => null,
            'category_id' => $categoryId,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'severity_id' => $severityId,
            'status_id' => $statusId,
        ]);

        return response()->json([
            'message' => 'Center issue report submitted successfully.',
            'data' => $this->formatReport($report->load([
                'center',
                'reporter',
                'handler',
                'category',
                'severityLevel',
                'status',
            ])),
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();

        $query = CenterIssueReport::with([
            'center',
            'reporter',
            'handler',
            'category',
            'severityLevel',
            'status',
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
            'data' => $this->formatReport($report),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $report = CenterIssueReport::with('status')->where('report_id', $id)->firstOrFail();

        if ($user->isEvacPersonnel()) {
            if ($report->reported_by !== $user->user_id) {
                return response()->json([
                    'message' => 'You can only update your own report.'
                ], 403);
            }

            // Check if report status is 'open' via the relationship
            if ($report->status && $report->status->status_key !== 'open') {
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

        $updateData = [];

        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }

        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }

        if (isset($validated['category'])) {
            $updateData['category_id'] = CenterIssueCategory::where('category_key', $validated['category'])->value('category_id');
        }

        if (isset($validated['severity'])) {
            $updateData['severity_id'] = SeverityLevel::where('severity_key', $validated['severity'])->value('severity_id');
        }

        $report->update($updateData);

        return response()->json([
            'message' => 'Center issue report updated successfully.',
            'data' => $this->formatReport($report->fresh([
                'center',
                'reporter',
                'handler',
                'category',
                'severityLevel',
                'status',
            ])),
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

        $statusId = CenterIssueReportStatus::where('status_key', $validated['status'])->value('status_id');

        $report->update([
            'status_id' => $statusId,
            'handled_by' => $user->user_id,
        ]);

        return response()->json([
            'message' => 'Center issue report status updated successfully.',
            'data' => $this->formatReport($report->fresh([
                'center.address',
                'reporter',
                'handler',
                'category',
                'severityLevel',
                'status',
            ])),
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $report = CenterIssueReport::with('status')->where('report_id', $id)->firstOrFail();

        if ($user->isEvacPersonnel()) {
            if ($report->reported_by !== $user->user_id) {
                return response()->json([
                    'message' => 'You can only delete your own report.'
                ], 403);
            }

            if ($report->status && $report->status->status_key !== 'open') {
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

    /**
     * Format a report for API response.
     * Flattens the lookup table relationships into simple string values
     * so the frontend can consume them as before (e.g. report.status, report.category, report.severity).
     */
    private function formatReport($report)
    {
        $data = $report->toArray();

        // Flatten lookup values to simple string keys for the frontend
        $data['status'] = $report->status->status_key ?? null;
        $data['category'] = $report->category->category_key ?? null;
        $data['severity'] = $report->severityLevel->severity_key ?? null;

        // Also provide the human-readable labels
        $data['status_label'] = $report->status->status_label ?? null;
        $data['category_label'] = $report->category->category_label ?? null;
        $data['severity_label'] = $report->severityLevel->severity_label ?? null;

        // Flatten reporter/handler names
        $data['reported_by_user'] = $report->reporter ?? null;
        $data['handled_by_user'] = $report->handler ?? null;

        return $data;
    }
}