<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\EvacuatedMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HouseholdMemberController extends Controller
{
    private function statusIds(string $table, string $column): array
    {
        return DB::connection('mysql_v2')
            ->table($table)
            ->pluck($column)
            ->toArray();
    }

    public function index($householdId)
    {
        $household = Household::with('members')
            ->where('household_id', $householdId)
            ->firstOrFail();

        return response()->json([
            'data' => $household->members
        ]);
    }

    public function store(Request $request, $householdId)
    {
        $request->validate([
            'first_name'           => 'required|string|max:100',
            'middle_name'          => 'nullable|string|max:100',
            'last_name'            => 'required|string|max:100',
            'birth_date'           => 'required|date',
            'gender_id'            => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('genders', 'gender_id'))],
            'relationship_id'      => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('relationships', 'relationship_id'))],
            'civil_status_id'      => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('civil_statuses', 'status_id'))],
            'vulnerable_group_ids' => 'nullable|array',
            'vulnerable_group_ids.*' => 'integer',
        ]);

        Household::where('household_id', $householdId)->firstOrFail();

        return DB::connection('mysql_v2')->transaction(function () use ($request, $householdId) {

            $member = HouseholdMember::create([
                'household_id'    => $householdId,
                'first_name'      => $request->first_name,
                'middle_name'     => $request->middle_name,
                'last_name'       => $request->last_name,
                'birth_date'      => $request->birth_date,
                'gender_id'       => $request->gender_id,
                'relationship_id' => $request->relationship_id,
                'civil_status_id' => $request->civil_status_id,
            ]);

            if ($request->has('vulnerable_group_ids')) {
                $member->vulnerableGroupDetails()->sync($request->vulnerable_group_ids);
            }

            return response()->json([
                'message' => 'Member added successfully.',
                'data'    => $member->fresh(['gender', 'relationship', 'civilStatus', 'vulnerableGroupDetails']),
            ], 201);
        });
    }

    public function update(Request $request, $householdId, $memberId)
    {
        $request->validate([
            'first_name'           => 'required|string|max:100',
            'middle_name'          => 'nullable|string|max:100',
            'last_name'            => 'required|string|max:100',
            'birth_date'           => 'required|date',
            'gender_id'            => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('genders', 'gender_id'))],
            'relationship_id'      => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('relationships', 'relationship_id'))],
            'civil_status_id'      => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('civil_statuses', 'status_id'))],
            'vulnerable_group_ids' => 'nullable|array',
            'vulnerable_group_ids.*' => 'integer',
        ]);

        $member = HouseholdMember::where('member_id', $memberId)
            ->where('household_id', $householdId)
            ->firstOrFail();

        return DB::connection('mysql_v2')->transaction(function () use ($request, $member) {
            $member->update($request->only([
                'first_name',
                'middle_name',
                'last_name',
                'birth_date',
                'gender_id',
                'relationship_id',
                'civil_status_id',
            ]));

            if ($request->has('vulnerable_group_ids')) {
                $member->vulnerableGroupDetails()->sync($request->vulnerable_group_ids);
            }

            return response()->json([
                'message' => 'Member updated successfully.',
                'data'    => $member->fresh(['gender', 'relationship', 'civilStatus', 'vulnerableGroupDetails']),
            ]);
        });
    }

    public function destroy($householdId, $memberId)
    {
        $user = Auth::user();

        if ($user->isEvacPersonnel()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return DB::connection('mysql_v2')->transaction(function () use ($householdId, $memberId) {

            $member = HouseholdMember::where('member_id', $memberId)
                ->where('household_id', $householdId)
                ->firstOrFail();

            $isLinkedToEvacuation = EvacuatedMember::where('member_id', $memberId)->exists();

            if ($isLinkedToEvacuation) {
                return response()->json([
                    'message' => 'Cannot delete this member because they are linked to an evacuation record. Mark them as Not Verified first.'
                ], 400);
            }

            $member->delete();

            return response()->json([
                'message' => 'Member removed successfully.'
            ]);
        });
    }

}