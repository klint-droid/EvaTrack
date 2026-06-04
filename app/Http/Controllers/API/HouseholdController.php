<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Household;
use App\Models\HouseholdStatus;
use App\Services\EvacuationService;
use App\Models\EvacuationRecord;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class HouseholdController extends Controller
{
    private function householdRelations(): array
    {
        return [
            'members',
            'members.gender',
            'members.relationship',
            'members.civilStatus',
            'members.vulnerableGroupDetails',
            'address',
            'currentEvacuation.center',
            'currentEvacuation.event',
            'currentEvacuation.unitAllocation.unit.type', 
            'currentEvacuation.verifier',                 
            'currentEvacuation.evacuatedMembers.member',
        ];
    }

    #[OA\Get(
        path: '/households',
        summary: 'List households',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Search query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'status', in: 'query', description: 'Filter by status (evacuated, not_evacuated)', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by evacuation center ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isEvacPersonnel() && !$user->assigned_center_id) {
            return response()->json(['message' => 'No center assigned.'], 403);
        }

        $evacuatedStatusId = HouseholdStatus::where('status_key', 'evacuated')->value('status_id');

        $page = $request->query('page', 1);
        $search = $request->query('q', '');
        $status = $request->query('status', '');
        $centerId = $request->query('center_id', '');
        $assignedCenterId = $user->isEvacPersonnel() ? $user->assigned_center_id : 'all';

        $cacheKey = "households_list_c{$assignedCenterId}_p{$page}_q" . md5($search) . "_s{$status}_ci{$centerId}";

        $results = Cache::tags(['households'])->remember($cacheKey, 300, function () use ($user, $evacuatedStatusId, $search, $status, $centerId) {
            $query = Household::withCount('members')->with([
                'address',
                'currentEvacuation.center',
                'currentEvacuation.unitAllocation.unit',
            ]);

            // Removed personnel siliog so personnel can see all households system-wide

            if (!empty($search)) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('household_name', 'LIKE', "%{$search}%")
                        ->orWhere('household_id', 'LIKE', "%{$search}%")
                        ->orWhere('contact_number', 'LIKE', "%{$search}%");
                });
            }

            if (!empty($status)) {
                if ($status === 'evacuated') {
                    $query->whereHas('currentEvacuation', function ($q) use ($evacuatedStatusId) {
                        $q->where('household_status_id', $evacuatedStatusId); 
                    });
                }

                if ($status === 'not_evacuated') {
                    $query->whereDoesntHave('currentEvacuation', function ($q) use ($evacuatedStatusId) {
                        $q->where('household_status_id', $evacuatedStatusId); 
                    });
                }
            }

            if (!empty($centerId)) {
                $query->whereHas('currentEvacuation', function ($q) use ($centerId, $evacuatedStatusId) {
                    $q->where('center_id', $centerId)
                        ->where('household_status_id', $evacuatedStatusId); 
                });
            }

            return $query->paginate(15);
        });

        return response()->json($results);
    }

    #[OA\Get(
        path: '/households/{id}',
        summary: 'Get household details',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Household not found')]
    public function show($id)
    {
        // Removed personnel check so they can view household details globally
        $household = Household::with($this->householdRelations())
            ->where('household_id', $id)
            ->firstOrFail();

        return response()->json(['data' => $household]);
    }

    #[OA\Post(
        path: '/households',
        summary: 'Create household',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['household_name'],
            properties: [
                new OA\Property(property: 'household_name', type: 'string'),
                new OA\Property(property: 'contact_number', type: 'string', nullable: true),
                new OA\Property(property: 'address_id', type: 'integer', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function store(Request $request, EvacuationService $service)
    {
        $user = Auth::user();

        if (!$user->isEvacPersonnel() && !$user->isEvacAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'household_name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'address_id'     => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !Address::where('address_id', $value)->exists()) {
                        $fail('The selected address is invalid.');
                    }
                }
            ],
        ]);

        $household = Household::create([
            'household_name' => $request->household_name,
            'contact_number' => $request->contact_number,
            'address_id'     => $request->address_id,
        ]);

        return response()->json([
            'message' => 'Household created successfully',
            'data'    => $household->fresh($this->householdRelations()),
        ], 201);
    }

    #[OA\Patch(
        path: '/households/{id}',
        summary: 'Update household and address',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'household_name', type: 'string'),
                new OA\Property(property: 'contact_number', type: 'string', nullable: true),
                new OA\Property(property: 'barangay', type: 'string', nullable: true),
                new OA\Property(property: 'street', type: 'string', nullable: true),
                new OA\Property(property: 'purok', type: 'string', nullable: true),
                new OA\Property(property: 'city', type: 'string', nullable: true),
                new OA\Property(property: 'province', type: 'string', nullable: true),
                new OA\Property(property: 'full_address', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Household not found')]
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isEvacPersonnel() && !$user->isEvacAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'household_name' => 'sometimes|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'barangay'       => 'nullable|string|max:255',
            'street'         => 'nullable|string|max:255',
            'purok'          => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'province'       => 'nullable|string|max:255',
            'full_address'   => 'nullable|string|max:500',
        ]);

        $household = Household::with('address')
            ->where('household_id', $id)
            ->firstOrFail();

        // Enforce personnel scoping guard - personnel can only edit if household belongs to their assigned center
        if ($user->isEvacPersonnel()) {
            $evacuation = $household->currentEvacuation()->first();
            $centerId = $evacuation ? $evacuation->center_id : null;
            if ($centerId !== $user->assigned_center_id) {
                return response()->json(['message' => 'Unauthorized. You can only edit households evacuated to your assigned center.'], 403);
            }
        }

        return DB::connection('mysql_v2')->transaction(function () use ($request, $household) {
            $household->update([
                'household_name' => $request->household_name ?? $household->household_name,
                'contact_number' => $request->contact_number ?? $household->contact_number,
            ]);

            if ($household->address) {
                $household->address->update([
                    'barangay'     => $request->barangay     ?? $household->address->barangay,
                    'street'       => $request->street       ?? $household->address->street,
                    'purok'        => $request->purok        ?? $household->address->purok,
                    'city'         => $request->city         ?? $household->address->city,
                    'province'     => $request->province     ?? $household->address->province,
                    'full_address' => $request->full_address ?? $household->address->full_address,
                ]);
            }

            return response()->json([
                'message' => 'Household updated successfully',
                'data'    => $household->fresh($this->householdRelations()),
            ]);
        });
    }

    #[OA\Get(
        path: '/households/search',
        summary: 'Search households',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Search query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Query required')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function search(Request $request)
    {
        $queryText = $request->input('q');

        if (!$queryText) {
            return response()->json(['message' => 'Search query is required'], 400);
        }

        $results = Household::where(function ($q) use ($queryText) {
            $q->where('household_name', 'LIKE', "%{$queryText}%")
                ->orWhere('household_id', 'LIKE', "%{$queryText}%")
                ->orWhere('contact_number', 'LIKE', "%{$queryText}%");
        })
            ->with($this->householdRelations())
            ->paginate(10);

        return response()->json($results);
    }

    #[OA\Delete(
        path: '/households/{id}',
        summary: 'Delete household',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 400, description: 'Household is currently evacuated')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Household not found')]
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isEvacAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $household = Household::where('household_id', $id)->firstOrFail();

        $evacuatedStatusId = HouseholdStatus::where('status_key', 'evacuated')->value('status_id');

        $isEvacuated = EvacuationRecord::where('household_id', $id)
            ->where('household_status_id', $evacuatedStatusId)
            ->exists();

        if ($isEvacuated) {
            return response()->json([
                'message' => 'Cannot delete a household that is currently evacuated.'
            ], 400);
        }

        $household->delete();

        return response()->json(['message' => 'Household deleted successfully.']);
    }
}