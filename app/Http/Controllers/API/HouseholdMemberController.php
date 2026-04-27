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
            'name'        => 'required|string|max:100',
            'age'         => 'required|integer|min:0|max:120',
            'gender'      => 'required|in:male,female,other',
            'relation'    => 'required|string|max:50',
            'is_pwd'      => 'boolean',
            'is_pregnant' => 'boolean',
        ]);

        Household::where('household_id', $householdId)->firstOrFail();

        return DB::connection('mysql_v2')->transaction(function () use ($request, $householdId) {

            $member = HouseholdMember::create([
                'household_id' => $householdId,
                'name'         => $request->name,
                'age'          => $request->age,
                'gender'       => $request->gender,
                'relation'     => $request->relation,
                'is_pwd'       => $request->boolean('is_pwd', false),
                'is_pregnant'  => $request->boolean('is_pregnant', false),
            ]);

            $this->syncHouseholdMemberCount($householdId);

            return response()->json([
                'message' => 'Member added successfully.',
                'data'    => $member,
            ], 201);
        });
    }

    public function update(Request $request, $householdId, $memberId)
    {
        $request->validate([
            'name'        => 'sometimes|string|max:100',
            'age'         => 'sometimes|integer|min:0|max:120',
            'gender'      => 'sometimes|in:male,female,other',
            'relation'    => 'sometimes|string|max:50',
            'is_pwd'      => 'sometimes|boolean',
            'is_pregnant' => 'sometimes|boolean',
        ]);

        $member = HouseholdMember::where('member_id', $memberId)
            ->where('household_id', $householdId)
            ->firstOrFail();

        $payload = $request->only([
            'name',
            'age',
            'gender',
            'relation',
            'is_pwd',
            'is_pregnant',
        ]);

        $member->update($payload);

        return response()->json([
            'message' => 'Member updated successfully.',
            'data'    => $member->fresh(),
        ]);
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

            $this->syncHouseholdMemberCount($householdId);

            return response()->json([
                'message' => 'Member removed successfully.'
            ]);
        });
    }

    private function syncHouseholdMemberCount($householdId): void
    {
        $count = HouseholdMember::where('household_id', $householdId)->count();

        Household::where('household_id', $householdId)
            ->update([
                'member_count' => $count
            ]);
    }
}