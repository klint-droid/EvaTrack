<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\EvacuationRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreEvacuationCenterRequest;
use App\Http\Requests\UpdateEvacuationCenterRequest;
use App\Models\Address;

class EvacuationCenterController extends Controller
{
    public function index()
    {
        $centers = EvacuationCenter::with('address')
            ->withCount([
                'evacuations as household_count' => function ($query) {
                    $query->where('status', 'evacuated');
                }
            ])
            ->withSum([
                'evacuations as current_occupancy' => function ($query) {
                    $query->where('status', 'evacuated');
                }
            ], 'evacuated_count')
            ->get();

        return response()->json($centers);
    }

    public function store(StoreEvacuationCenterRequest $request)
    {
        $user = Auth::user();

        if (!$user->isEvacAdmin()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validated();

        return DB::transaction(function () use ($data) {

            $address = Address::create([
                'region' => $data['region'] ?? null,
                'province' => $data['province'] ?? null,
                'city' => $data['city'] ?? null,
                'barangay' => $data['barangay'] ?? null,
                'street' => $data['street'] ?? null,
                'purok' => $data['purok'] ?? null,
                'full_address' => $data['full_address'] ?? null,
            ]);

            $center = EvacuationCenter::create([
                'name' => $data['name'],
                'address_id' => $address->address_id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'capacity' => $data['capacity'],
            ]);

            return response()->json(
                $center->load('address'),
                201
            );
        });
    }

    public function show(EvacuationCenter $evacuation_center)
    {
        return response()->json($evacuation_center->load('address'));
    }

    public function update(UpdateEvacuationCenterRequest $request, EvacuationCenter $evacuation_center)
    {
        $user = Auth::user();

        if (!$user->isEvacAdmin()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validated();

        return DB::transaction(function () use ($data, $evacuation_center) {

            $evacuation_center->address->update([
                'region' => $data['region'] ?? $evacuation_center->address->region,
                'province' => $data['province'] ?? $evacuation_center->address->province,
                'city' => $data['city'] ?? $evacuation_center->address->city,
                'barangay' => $data['barangay'] ?? $evacuation_center->address->barangay,
                'street' => $data['street'] ?? $evacuation_center->address->street,
                'purok' => $data['purok'] ?? $evacuation_center->address->purok,
                'full_address' => $data['full_address'] ?? $evacuation_center->address->full_address,
            ]);

            $evacuation_center->update([
                'name' => $data['name'],
                'location' => $data['location'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'capacity' => $data['capacity'],
            ]);

            return response()->json(
                $evacuation_center->load('address')
            );
        });
    }

    public function destroy(EvacuationCenter $evacuation_center)
    {
        $user = Auth::user();

        if (!$user->isEvacAdmin()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $evacuation_center->delete();

        return response()->json([
            'message' => 'Evacuation Center deleted successfully'
        ]);
    }

    public function capacity(EvacuationCenter $evacuation_center)
    {
        $current = EvacuationRecord::where('center_id', $evacuation_center->evacuation_center_id)
            ->where('status', 'evacuated')
            ->count();

        return response()->json([
            'capacity' => $evacuation_center->capacity,
            'current_occupancy' => $current,
            'available_space' => max(0, $evacuation_center->capacity - $current)
        ]);
    }
}