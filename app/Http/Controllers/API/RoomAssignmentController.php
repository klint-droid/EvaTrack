<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;
use App\Models\RoomAssignment;

class RoomAssignmentController extends Controller
{
    public function assign(Request $request, $room_id){
        $request->validate([
            'household_id' => 'required|exists:households,household_id',
            'evacuation_id' => 'required|exists:evacuation_records,evacuation_id'
        ]);

        $room = Room::withCount('assignments')->findOrFail($room_id);

        // Capacity check
        if($room->assignments_count >= $room->max_capacity){
            return response()->json([
                'message' => 'Room is full'
            ], 400);
        }

        // Prevent duplicate per evacuation
        if(RoomAssignment::where('household_id', $request->household_id)
            ->where('evacuation_id', $request->evacuation_id)
            ->exists()){
            return response()->json([
                'message' => 'Household already assigned in this evacuation'
            ], 400);
        }

        // Validate evacuation center match
        $evacuation = \App\Models\Evacuation::findOrFail($request->evacuation_id);

        if ($room->evacuation_center_id !== $evacuation->evacuation_center_id) {
            return response()->json([
                'message' => 'Room does not belong to this evacuation center'
            ], 400);
        }

        return RoomAssignment::create([
            'room_id' => $room_id,
            'evacuation_id' => $request->evacuation_id,
            'household_id' => $request->household_id,
            'assigned_by' => Auth::user()->user_id
        ]);
    }

    public function remove($room_id, $household_id){
        RoomAssignment::where('room_id', $room_id)
            ->where('household_id', $household_id)
            ->delete();

        return response()->json([
            'message' => 'Room assignment removed'
        ], 200);
    }
}