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
        $disasterTypes = DisasterType::query()
            ->where('is_active', true)
            ->orderBy('type_name')
            ->get();

        return response()->json(['data' => $disasterTypes]);
    }
}

