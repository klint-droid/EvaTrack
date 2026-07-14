<?php

namespace App\Domains\CenterIssueReports\Controllers;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Domains\CenterIssueReports\Requests\StoreCenterIssueReportRequest;
use App\Domains\CenterIssueReports\Requests\UpdateCenterIssueReportRequest;
use App\Domains\CenterIssueReports\Requests\UpdateCenterIssueReportStatusRequest;
use App\Domains\CenterIssueReports\DTOs\CenterIssueReportDTO;
use App\Domains\CenterIssueReports\DTOs\CenterIssueReportFilterDTO;
use App\Domains\CenterIssueReports\Actions\ListCenterIssueReportsAction;
use App\Domains\CenterIssueReports\Actions\CreateCenterIssueReportAction;
use App\Domains\CenterIssueReports\Actions\GetCenterIssueReportAction;
use App\Domains\CenterIssueReports\Actions\UpdateCenterIssueReportAction;
use App\Domains\CenterIssueReports\Actions\UpdateCenterIssueReportStatusAction;
use App\Domains\CenterIssueReports\Actions\DeleteCenterIssueReportAction;
use OpenApi\Attributes as OA;
use Exception;

class CenterIssueReportController extends BaseApiController
{
    #[OA\Get(
        path: '/center-issue-reports',
        summary: 'List center issue reports',
        security: [['bearerAuth' => []]],
        tags: ['Support Requests']
    )]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by evacuation center ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'category', in: 'query', description: 'Filter by category key', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'severity', in: 'query', description: 'Filter by severity key', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'status', in: 'query', description: 'Filter by status key', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Search query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limit number of results', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function index(Request $request, ListCenterIssueReportsAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $user = Auth::user();
        $enforcedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;
        
        $filter = CenterIssueReportFilterDTO::fromRequest($request);

        $result = $action->execute($filter, $enforcedCenterId);

        return response()->json([
            'data' => $result['data']->map(fn($report) => $this->formatReport($report)),
            'summary' => $result['summary'],
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
                new OA\Property(property: 'category', type: 'string'),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'severity', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 422, description: 'Validation errors')]
    public function store(StoreCenterIssueReportRequest $request, CreateCenterIssueReportAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $user = Auth::user();
        $enforcedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;
        // Super admin/evac admin can specify evacuation_center_id, handled by DTO mapping

        $dto = CenterIssueReportDTO::fromRequest($request);
        $attachment = $request->file('attachment');

        $report = $action->execute($dto, $user->user_id, $enforcedCenterId, $attachment);

        return response()->json([
            'message' => 'Center issue report submitted successfully.',
            'data' => $this->formatReport($report),
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
    public function show($id, GetCenterIssueReportAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $user = Auth::user();
        $enforcedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;

        $report = $action->execute($id, $enforcedCenterId);

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
                new OA\Property(property: 'category', type: 'string'),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'severity', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 400, description: 'Only open reports can be edited')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Report not found')]
    public function update(UpdateCenterIssueReportRequest $request, $id, UpdateCenterIssueReportAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $user = Auth::user();
        $enforcedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;

        $dto = CenterIssueReportDTO::fromRequest($request);
        $attachment = $request->file('attachment');

        try {
            $report = $action->execute($id, $dto, $user, $enforcedCenterId, $attachment);

            return response()->json([
                'message' => 'Center issue report updated successfully.',
                'data' => $this->formatReport($report),
            ]);
        } catch (Exception $e) {
            $status = in_array($e->getCode(), [400, 403, 404]) ? $e->getCode() : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
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
                new OA\Property(property: 'status', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Report not found')]
    public function updateStatus(UpdateCenterIssueReportStatusRequest $request, $id, UpdateCenterIssueReportStatusAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        try {
            $report = $action->execute($id, $request->validated('status'), Auth::id());

            return response()->json([
                'message' => 'Center issue report status updated successfully.',
                'data' => $this->formatReport($report),
            ]);
        } catch (Exception $e) {
            $status = $e->getCode() === 404 ? 404 : 422;
            return response()->json(['message' => $e->getMessage()], $status);
        }
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
    public function destroy($id, DeleteCenterIssueReportAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $user = Auth::user();
        $enforcedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;

        try {
            $action->execute($id, $user, $enforcedCenterId);

            return response()->json([
                'message' => 'Center issue report deleted successfully.'
            ]);
        } catch (Exception $e) {
            $status = in_array($e->getCode(), [400, 403, 404]) ? $e->getCode() : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    private function formatReport($report)
    {
        $data = $report->toArray();

        $data['status'] = $report->status->status_key ?? null;
        $data['category'] = $report->category->category_key ?? null;
        $data['severity'] = $report->severityLevel->severity_key ?? null;

        $data['status_label'] = $report->status->status_label ?? null;
        $data['category_label'] = $report->category->category_label ?? null;
        $data['severity_label'] = $report->severityLevel->severity_label ?? null;

        $data['reported_by_user'] = $report->reporter ?? null;
        $data['handled_by_user'] = $report->handler ?? null;
        
        $data['attachment_url'] = !empty($report->attachment_path) 
            ? url(Storage::url($report->attachment_path)) 
            : null;

        return $data;
    }
}
