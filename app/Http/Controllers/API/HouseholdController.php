<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Household;
use App\Models\HouseholdStatus;
use App\Services\EvacuationService;
use App\Models\EvacuationRecord;
use App\Models\Address;
use Illuminate\Support\Facades\DB;

class HouseholdController extends Controller
{
    private function householdRelations(): array
    {
        return [
            'members',
            'address',
            'currentEvacuation.center',
            'currentEvacuation.event',
            'currentEvacuation.unitAllocation.unit.type', 
            'currentEvacuation.verifier',                 
            'currentEvacuation.evacuatedMembers.member',
        ];
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $evacuatedStatusId = HouseholdStatus::where('status_key', 'evacuated')->value('status_id');

        $query = Household::withCount('members')->with([
            'address',
            'currentEvacuation.center',
            'currentEvacuation.event',
            'currentEvacuation.unitAllocation.unit.type', 
            'currentEvacuation.verifier',                 
            'currentEvacuation.evacuatedMembers.member',
        ]);

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                return response()->json(['message' => 'No center assigned.'], 403);
            }

            $query->whereHas('currentEvacuation', function ($q) use ($user, $evacuatedStatusId) {
                $q->where('center_id', $user->assigned_center_id)
                    ->where('household_status_id', $evacuatedStatusId); 
            });
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($builder) use ($search) {
                $builder->where('household_name', 'LIKE', "%{$search}%")
                    ->orWhere('household_id', 'LIKE', "%{$search}%")
                    ->orWhere('contact_number', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'evacuated') {
                $query->whereHas('currentEvacuation', function ($q) use ($evacuatedStatusId) {
                    $q->where('household_status_id', $evacuatedStatusId); 
                });
            }

            if ($request->status === 'not_evacuated') {
                $query->whereDoesntHave('currentEvacuation', function ($q) use ($evacuatedStatusId) {
                    $q->where('household_status_id', $evacuatedStatusId); 
                });
            }
        }

        if ($request->filled('center_id')) {
            $query->whereHas('currentEvacuation', function ($q) use ($request, $evacuatedStatusId) {
                $q->where('center_id', $request->center_id)
                    ->where('household_status_id', $evacuatedStatusId); 
            });
        }

        return response()->json($query->paginate(15));
    }

    public function show($id)
    {
        $user = Auth::user();

        $household = Household::with($this->householdRelations())
            ->where('household_id', $id)
            ->firstOrFail();

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                return response()->json(['message' => 'No evacuation center assigned.'], 403);
            }

            $evacuation = $household->currentEvacuation;

            if (!$evacuation || $evacuation->center_id !== $user->assigned_center_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return response()->json(['data' => $household]);
    }

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