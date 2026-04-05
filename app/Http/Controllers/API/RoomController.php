<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->assigned_evacuation_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        $rooms = Room::where('evacuation_center_id', $user->assigned_evacuation_center_id)
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->room_id,
                    'room_number' => $room->room_number,
                    'max_capacity' => $room->max_capacity,
                    'current_occupancy' => $room->current_occupancy,
                    'status' => $room->status,
                ];
            });

        return response()->json([
            'data' => $rooms
        ]);
    }
    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        return response()->json([
            'message' => 'Room created successfully',
            'data' => $room
        ]);
    }

    public function show(Room $room)
    {
        return $room->load([
            'evacuationCenter',
            'assignments'
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        if ($request->max_capacity < $room->current_occupancy) {
            return response()->json([
                'message' => 'Capacity cannot be less than current occupancy'
            ], 400);
        }

        $room->update($request->validated());

        return response()->json([
            'message' => 'Room updated successfully',
            'data' => $room
        ]);
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }
}