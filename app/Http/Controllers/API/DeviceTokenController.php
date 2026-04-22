<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceToken;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'player_id' => 'required|string',
            'household_id' => 'required|string'
        ]);

        // Avoid duplicates
        DeviceToken::updateOrCreate(
            ['player_id' => $request->player_id],
            ['household_id' => $request->household_id]
        );

        return response()->json([
            'message' => 'Device registered successfully'
        ]);
    }
}