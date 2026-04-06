<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\EvacuationCenter;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $centerId = $request->query('evacuation_center_id') 
            ?? $user->assigned_evacuation_center_id;

        if (!$centerId) {
            return response()->json([
                'message' => 'No evacuation center specified'
            ], 400);
        }

        $rooms = Room::where('evacuation_center_id', $centerId)->get();

        return response()->json(['data' => $rooms]);
    }

    public function byCenter(EvacuationCenter $evacuation_center)
    {
        $rooms = Room::where('evacuation_center_id', $evacuation_center->evacuation_center_id)->get();

        return response()->json(['data' => $rooms]);
    }

    public function store(StoreRoomRequest $request)
        {
            $data = $request->validated();
            $user = Auth::user();

            $center = EvacuationCenter::findOrFail($data['evacuation_center_id']);

            if (
                !$user->isAdmin() &&
                !$user->isSuperAdmin() &&
                $user->assigned_evacuation_center_id !== $center->evacuation_center_id
            ) {
                return response()->json([
                    'message' => 'Unauthorized to add rooms to this evacuation center'
                ], 403);
            }

            $totalRoomCapacity = Room::where('evacuation_center_id', $center->evacuation_center_id)
                ->sum('max_capacity');

            if (($totalRoomCapacity + $data['max_capacity']) > $center->capacity) {
                return response()->json([
                    'message' => 'Total room capacity exceeds evacuation center capacity'
                ], 422);
            }

            $data['room_id'] = 'RM-' . Str::uuid();
            $data['current_occupancy'] = 0;

            $room = Room::create($data);

            return response()->json([
                'message' => 'Room created successfully',
                'data' => $room
            ], 201);
        }

    public function show(Room $room)
    {
        return response()->json([
            'data' => $room->load([
                'evacuationCenter',
                'assignments'
            ])
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        $data = $request->validated();
        $user = Auth::user();

        if (
            !$user->isAdmin() &&
            !$user->isSuperAdmin() &&
            $user->assigned_evacuation_center_id !== $room->evacuation_center_id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($data['max_capacity'] < $room->current_occupancy) {
            return response()->json([
                'message' => 'Capacity cannot be less than current occupancy'
            ], 400);
        }

        $center = EvacuationCenter::findOrFail($room->evacuation_center_id);

        $otherRoomsCapacity = Room::where('evacuation_center_id', $room->evacuation_center_id)
            ->where('room_id', '!=', $room->room_id)
            ->sum('max_capacity');

        if (($otherRoomsCapacity + $data['max_capacity']) > $center->capacity) {
            return response()->json([
                'message' => 'Total room capacity exceeds evacuation center capacity'
            ], 422);
        }

        $room->update($data);

        return response()->json([
            'message' => 'Room updated successfully',
            'data' => $room
        ]);
    }

    public function destroy(Room $room)
    {
        $user = Auth::user();

        if (
            !$user->isAdmin() &&
            !$user->isSuperAdmin() &&
            $user->assigned_evacuation_center_id !== $room->evacuation_center_id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($room->current_occupancy > 0) {
            return response()->json([
                'message' => 'Cannot delete room with occupants'
            ], 400);
        }

        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }
}