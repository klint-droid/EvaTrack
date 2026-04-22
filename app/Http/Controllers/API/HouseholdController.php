<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Household;
use App\Services\EvacuationService;

class HouseholdController extends Controller
{
    public function index()
    {
        return response()->json(
            Household::with('members')->paginate(10)
        );
    }

    public function show($id)
    {
        $household = Household::with('members')
            ->where('household_id', $id)
            ->firstOrFail();

        return response()->json($household);
    }

    public function store(Request $request, EvacuationService $service)
    {
        $request->validate([
            'household_name' => 'required|string|max:255',
            'member_count' => 'required|integer|min:1'
        ]);

        $household = Household::create([
            'household_id' => $service->generateHouseholdId('new'),
            'household_name' => $request->household_name,
            'member_count' => $request->member_count,
            'address' => 'N/A'
        ]);

        return response()->json([
            'message' => 'Household created successfully',
            'data' => $household
        ], 201);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([
                'message' => 'Search query is required'
            ], 400);
        }

        $results = Household::where(function ($qBuilder) use ($query) {
                $qBuilder->where('household_name', 'LIKE', "%{$query}%")
                        ->orWhere('household_id', 'LIKE', "%{$query}%");
            })
            ->with('members')
            ->paginate(10);

        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'No results found',
                'data' => []
            ], 200);
        }

        return response()->json($results);
    }
}