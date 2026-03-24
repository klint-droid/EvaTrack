<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Household;

class HouseholdController extends Controller
{
    public function index(Request $request){
        return response()->json(Household::paginate(10));
    }

    public function show($id){
        $household = Household::findOrFail($id);

        if(!$household){
            return response()->json(['message' => 'Household not found'], 404);
        }

        return response()->json($household);
    }

    public function findQR($qr_code){
        $household = Household::where('household_id', $qr_code)->first();

        if(!$household){
            return response()->json(['message' => 'QR code not found'], 404);
        }

        return response()->json($household);
    }

    public function search(Request $request){
        $query = $request->input('q');

        if(!$query){
            return response()->json(['message' => 'Query parameter is required'], 400);
        }

        $households = Household::where(function ($q) use ($query) {
            $q->where('household_name', 'LIKE', "%{$query}%")
            ->orWhere('household_id', 'LIKE', "%{$query}%");
        })->paginate(10);

        return response()->json($households);
    }
}
