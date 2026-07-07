<?php

namespace App\Http\Controllers\API;

use App\Models\CenterIssueReport;
use App\Models\CenterIssueReportStatus;
use App\Models\CenterIssueCategory;
use App\Models\SeverityLevel;
use App\Http\Requests\StoreCenterIssueReportRequest;
use App\Http\Requests\UpdateCenterIssueReportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class CenterIssueReportController extends BaseApiController
{
    #[OA\Get(
        path: '/center-issue-reports',
        summary: 'List center issue reports',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by evacuation center ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'category', in: 'query', description: 'Filter by category key (incident, facility_issue, health_issue, safety_issue, other)', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'severity', in: 'query', description: 'Filter by severity key (low, medium, high, critical)', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'status', in: 'query', description: 'Filter by status key (open, in_progress, resolved, closed)', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Search query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limit number of results', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function index(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $query = CenterIssueReport::with([
            'center',
            'reporter',
            'handler',
            'category',
            'severityLevel',
            'status',
        ]);

        $query = $this->applyCenterFilter($query, $request);

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

        $summary = [
            'open' => $openStatusId ? (clone $query)->where('status_id', $openStatusId)->count() : 0,
            'in_progress' => $inProgressStatusId ? (clone $query)->where('status_id', $inProgressStatusId)->count() : 0,
            'resolved' => $resolvedStatusId ? (clone $query)->where('status_id', $resolvedStatusId)->count() : 0,
            'critical' => $criticalSeverityId ? (clone $query)->where('severity_id', $criticalSeverityId)->count() : 0,
        ];

        $limit = (int)$request->query('limit', 0);
        if ($limit > 0) {
            $query->limit($limit);
        }

        return response()->json([
            'data' => $query->latest('created_at')->get()->map(function ($report) {
                return $this->formatReport($report);
            }),
            'summary' => $summary,
        ]);
    }

    #[OA\Post(
        path: '/center-issue-reports',
        summary: 'Submit a new center issue report',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['category', 'title', 'description', 'severity'],
            properties: [
                new OA\Property(property: 'evacuation_center_id', type: 'integer', nullable: true),
                new OA\Property(property: 'category', type: 'string', enum: ['incident', 'facility_issue', 'health_issue', 'safety_issue', 'other']),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'severity', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 422, description: 'Validation errors')]
    public function store(StoreCenterIssueReportRequest $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $validated = $request->validated();
        $centerId = $this->resolveUserCenterId($request);

        $categoryId = CenterIssueCategory::where('category_key', $validated['category'])->value('category_id');
        $severityId = SeverityLevel::where('severity_key', $validated['severity'])->value('severity_id');
        $statusId = CenterIssueReportStatus::where('status_key', 'open')->value('status_id');

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('center_issues', 'public');
        }

        $report = CenterIssueReport::create([
            'evacuation_center_id' => $centerId,
            'reported_by' => Auth::id(),
            'handled_by' => null,
            'category_id' => $categoryId,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'severity_id' => $severityId,
            'status_id' => $statusId,
            'attachment_path' => $attachmentPath,
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

    #[OA\Get(
        path: '/center-issue-reports/{id}',
        summary: 'Get center issue report details',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Report ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Report not found')]
    public function show($id)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $query = CenterIssueReport::with([
            'center',
            'reporter',
            'handler',
            'category',
            'severityLevel',
            'status',
        ])->where('report_id', $id);

        $query = $this->applyCenterFilter($query);

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

    #[OA\Patch(
        path: '/center-issue-reports/{id}',
        summary: 'Update center issue report details',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Report ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'category', type: 'string', enum: ['incident', 'facility_issue', 'health_issue', 'safety_issue', 'other']),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'severity', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 400, description: 'Only open reports can be edited')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Report not found')]
    public function update(UpdateCenterIssueReportRequest $request, $id)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $user = Auth::user();
        $report = CenterIssueReport::with('status')->where('report_id', $id)->firstOrFail();
        
        $this->checkCenterOwnership($report->evacuation_center_id);

        if ($user->isEvacPersonnel()) {
            if ($report->reported_by !== $user->user_id) {
                return response()->json([
                    'message' => 'You can only update your own report.'
                ], 403);
            }

            if ($report->status && $report->status->status_key !== 'open') {
                return response()->json([
                    'message' => 'Only open reports can be edited.'
                ], 400);
            }
        }

        $validated = $request->validated();

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

        if ($request->hasFile('attachment')) {
            if ($report->attachment_path) {
                Storage::disk('public')->delete($report->attachment_path);
            }
            $updateData['attachment_path'] = $request->file('attachment')->store('center_issues', 'public');
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

    #[OA\Patch(
        path: '/center-issue-reports/{id}/status',
        summary: 'Update status of center issue report',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Report ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(property: 'status', type: 'string', enum: ['open', 'in_progress', 'resolved', 'closed']),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Report not found')]
    public function updateStatus(Request $request, $id)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $report = CenterIssueReport::where('report_id', $id)->firstOrFail();

        $statusId = CenterIssueReportStatus::where('status_key', $validated['status'])->value('status_id');

        $report->update([
            'status_id' => $statusId,
            'handled_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Center issue report status updated successfully.',
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

    #[OA\Delete(
        path: '/center-issue-reports/{id}',
        summary: 'Delete center issue report',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Report ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 400, description: 'Only open reports can be deleted')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Report not found')]
    public function destroy($id)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $user = Auth::user();
        $report = CenterIssueReport::with('status')->where('report_id', $id)->firstOrFail();
        
        $this->checkCenterOwnership($report->evacuation_center_id);

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
        
        $data['attachment_url'] = !empty($report->attachment_path) 
            ? url(Storage::url($report->attachment_path)) 
            : null;

        return $data;
    }
}