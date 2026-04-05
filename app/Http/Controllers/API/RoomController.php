<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;

class RoomController extends Controller
{
    public function index(){
        return Room::withCount('assignments')->get()->map(function($room){
            return [
                'id' => $room->room_id,
                'room_number' => $room->room_number,
                'max_capacity' => $room->max_capacity,
                'current_occupancy' => $room->assignments_count,
            ];
        });
    }

    public function store(Request $request){
        $request->validate([
            'evacuation_center_id' => 'required|exists:evacuation_centers,evacuation_center_id',
            'room_number' => 'required|string|max:20',
            'max_capacity' => 'required|integer|min:1',
        ]);

        return Room::create([
            'evacuation_center_id' => $request->evacuation_center_id,
            'room_number' => $request->room_number,
            'max_capacity' => $request->max_capacity,
        ]);
    }

    public function show($id){
        return Room::with('evacuationCenter')
            ->with('assignments')
            ->withCount('assignments')
            ->findOrFail($id);
    }

    public function update(Request $request, $id){
        $request->validate([
            'evacuation_center_id' => 'required|exists:evacuation_centers,evacuation_center_id',
            'room_number' => 'required|string|max:20',
            'max_capacity' => 'required|integer|min:1',
        ]);

        $room = Room::findOrFail($id);

        $room->update([
            'evacuation_center_id' => $request->evacuation_center_id,
            'room_number' => $request->room_number,
            'max_capacity' => $request->max_capacity,
        ]);

        return $room;
    }

    public function destroy($id){
        Room::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }
}