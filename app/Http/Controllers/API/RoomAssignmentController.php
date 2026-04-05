<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Evacuation;
use App\Http\Requests\StoreRoomAssignmentRequest;

class RoomAssignmentController extends Controller
{
    public function assign(StoreRoomAssignmentRequest $request, Room $room)
    {
        return DB::transaction(function () use ($request, $room) {

            $room = Room::lockForUpdate()->findOrFail($room->room_id);

            if ($room->current_occupancy >= $room->max_capacity) {
                return response()->json([
                    'message' => 'Room is full'
                ], 400);
            }

            if (RoomAssignment::where('household_id', $request->household_id)
                ->where('evacuation_id', $request->evacuation_id)
                ->exists()) {
                return response()->json([
                    'message' => 'Household already assigned in this evacuation'
                ], 400);
            }

            $evacuation = Evacuation::findOrFail($request->evacuation_id);

            if ($room->evacuation_center_id !== $evacuation->evacuation_center_id) {
                return response()->json([
                    'message' => 'Room does not belong to this evacuation center'
                ], 400);
            }

            $assignment = RoomAssignment::create([
                'room_id' => $room->room_id,
                'evacuation_id' => $request->evacuation_id,
                'household_id' => $request->household_id,
                'assigned_by' => Auth::user()->user_id,
                'is_self_selected' => false
            ]);

            $room->increment('current_occupancy');

            return response()->json([
                'message' => 'Household assigned successfully',
                'data' => $assignment
            ]);
        });
    }

    public function remove(Room $room, $household)
    {
        return DB::transaction(function () use ($room, $household) {

            $assignment = RoomAssignment::where('room_id', $room->room_id)
                ->where('household_id', $household)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'message' => 'Assignment not found'
                ], 404);
            }

            $assignment->delete();

            $room->where('room_id', $room->room_id)
                ->where('current_occupancy', '>', 0)
                ->decrement('current_occupancy');

            return response()->json([
                'message' => 'Room assignment removed'
            ]);
        });
    }
}