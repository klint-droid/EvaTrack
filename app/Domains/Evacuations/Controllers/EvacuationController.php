<?php

namespace App\Domains\Evacuations\Controllers;

use App\Http\Controllers\API\BaseApiController;
use App\Domains\Evacuations\Actions\ListEvacuationRecordsAction;
use App\Domains\Evacuations\Actions\ScanQREvacuationAction;
use App\Domains\Evacuations\Actions\VerifyManualEvacuationAction;
use App\Domains\Evacuations\Actions\CheckoutEvacuationAction;
use App\Domains\Evacuations\Actions\UpdateEvacuatedMemberStatusAction;
use App\Domains\Evacuations\Actions\DeleteEvacuationRecordAction;
use App\Domains\Evacuations\DTOs\EvacuationFilterDTO;
use App\Domains\Evacuations\DTOs\AdmissionDTO;
use App\Domains\Evacuations\Requests\StoreEvacuationRequest;
use App\Domains\Evacuations\Requests\VerifyManualEvacuationRequest;
use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Exceptions\HouseholdAlreadyEvacuatedException;
use App\Exceptions\MembersAlreadyEvacuatedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class EvacuationController extends BaseApiController
{
    protected function resolveUserCenterId(Request $request): string
    {
        $user = Auth::user();
        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                abort(403, 'No center assigned.');
            }
            return (string) $user->assigned_center_id;
        }

        $centerId = $request->input('center_id') ?? $request->query('center_id');
        if (!$centerId) {
            abort(400, 'center_id is required for admins.');
        }

        return (string) $centerId;
    }

    #[OA\Get(
        path: '/evacuations',
        summary: 'List evacuation records',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'household_status_id', in: 'query', description: 'Filter by household status ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by evacuation center ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function index(Request $request, ListEvacuationRecordsAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $assignedCenterId = Auth::user()->isEvacPersonnel() ? Auth::user()->assigned_center_id : null;
        $filters = EvacuationFilterDTO::fromRequest($request, $assignedCenterId);

        return response()->json([
            'data' => $action->execute($filters)
        ]);
    }

    #[OA\Get(
        path: '/evacuations/{id}',
        summary: 'Get evacuation record details',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Evacuation record ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Evacuation not found')]
    public function show(int $id, EvacuationRepositoryInterface $repository)
    {
        $user = Auth::user();
        $centerId = $user->isEvacPersonnel() ? $user->assigned_center_id : null;

        return response()->json([
            'data' => $repository->findById($id, $centerId)
        ]);
    }

    #[OA\Post(
        path: '/evacuations/process-scan',
        summary: 'Verify admission using QR code scan',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['household_id'],
            properties: [
                new OA\Property(property: 'household_id', type: 'string'),
                new OA\Property(property: 'event_id', type: 'integer', nullable: true),
                new OA\Property(property: 'member_ids', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Household verified successfully')]
    #[OA\Response(response: 400, description: 'Invalid request or already evacuated')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function scan(StoreEvacuationRequest $request, ScanQREvacuationAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $centerId = $this->resolveUserCenterId($request);
        $dto = AdmissionDTO::fromRequest($request, $centerId, Auth::id(), 'qr');

        try {
            return response()->json([
                'message' => 'Household verified successfully',
                'data'    => $action->execute($dto)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: '/evacuations/verify-manual',
        summary: 'Manually verify household for admission',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['household_id'],
            properties: [
                new OA\Property(property: 'household_id', type: 'string'),
                new OA\Property(property: 'event_id', type: 'integer', nullable: true),
                new OA\Property(property: 'member_ids', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Household verified successfully')]
    #[OA\Response(response: 400, description: 'Invalid request or already evacuated')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function verifyManual(VerifyManualEvacuationRequest $request, VerifyManualEvacuationAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $centerId = $this->resolveUserCenterId($request);
        $dto = AdmissionDTO::fromRequest($request, $centerId, Auth::id(), 'manual');

        try {
            return response()->json([
                'message' => 'Household verified successfully',
                'data'    => $action->execute($dto)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: '/evacuations/admit',
        summary: 'Admit household to center',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['household_id'],
            properties: [
                new OA\Property(property: 'household_id', type: 'string'),
                new OA\Property(property: 'member_count', type: 'integer', minimum: 1),
                new OA\Property(property: 'event_id', type: 'integer', nullable: true),
                new OA\Property(property: 'member_ids', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Admission complete')]
    #[OA\Response(response: 400, description: 'Invalid request or already evacuated')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function admit(Request $request, VerifyManualEvacuationAction $action)
    {
        // Admission behaves the exact same way as verifyManual now
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $centerId = $this->resolveUserCenterId($request);
        $dto = AdmissionDTO::fromRequest($request, $centerId, Auth::id(), 'manual');

        try {
            return response()->json([
                'message' => 'Admission complete',
                'data'    => $action->execute($dto)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: '/evacuations/{evacuationId}/checkout',
        summary: 'Checkout household from center',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'evacuationId', in: 'path', description: 'Evacuation ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Household checked out successfully')]
    #[OA\Response(response: 400, description: 'Already checked out')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function checkout(int $evacuationId, Request $request, CheckoutEvacuationAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $centerId = $this->resolveUserCenterId($request);

        try {
            $record = $action->execute($evacuationId, $centerId);

            return response()->json([
                'message' => 'Household checked out successfully',
                'data'    => $record
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Patch(
        path: '/evacuations/{evacuationId}/members/{memberId}/status',
        summary: 'Update status of specific evacuated member',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'evacuationId', in: 'path', description: 'Evacuation ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'memberId', in: 'path', description: 'Member ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(property: 'status', type: 'string', enum: ['Inside Center', 'Checked Out', 'Transferred']),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Member status updated')]
    #[OA\Response(response: 400, description: 'Invalid request')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function updateMemberStatus(int $evacuationId, int $memberId, Request $request, UpdateEvacuatedMemberStatusAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $request->validate([
            'status' => 'required|string|in:Inside Center,Checked Out,Transferred'
        ]);

        $centerId = $this->resolveUserCenterId($request);

        try {
            $action->execute($evacuationId, $memberId, $request->status, $centerId);
            return response()->json(['message' => 'Member status updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Delete(
        path: '/evacuations/{evacuationId}',
        summary: 'Delete evacuation record',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'evacuationId', in: 'path', description: 'Evacuation ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function deleteRecord(int $evacuationId, Request $request, DeleteEvacuationRecordAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $centerId = $this->resolveUserCenterId($request);

        try {
            $action->execute($evacuationId, $centerId);
            return response()->json(['message' => 'Evacuation record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
