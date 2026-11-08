<?php

namespace App\Domains\EvacuationEvents\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domains\EvacuationEvents\Models\DisasterType;
use OpenApi\Attributes as OA;

class DisasterTypeController extends Controller
{
    #[OA\Get(
        path: '/disaster-types',
        summary: 'List active disaster types',
        description: 'Get all active disaster types.',
        tags: ['Lookups']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function index(){
        if (DisasterType::count() === 0) {
            $now = now();
            DisasterType::upsert([
                ['type_id' => 1, 'type_code' => 'EQ', 'type_name' => 'Earthquake',        'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['type_id' => 2, 'type_code' => 'FL', 'type_name' => 'Flood',             'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['type_id' => 3, 'type_code' => 'TC', 'type_name' => 'Tropical Cyclone',  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['type_id' => 4, 'type_code' => 'VF', 'type_name' => 'Volcanic Eruption', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['type_id' => 5, 'type_code' => 'FR', 'type_name' => 'Fire',              'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ], ['type_id'], ['type_code', 'type_name', 'is_active']);
        }

        $disasterTypes = DisasterType::query()
            ->where('is_active', true)
            ->orderBy('type_name')
            ->get();

        return response()->json(['data' => $disasterTypes]);
    }
}

