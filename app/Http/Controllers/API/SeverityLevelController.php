<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeverityLevel;
use OpenApi\Attributes as OA;

class SeverityLevelController extends Controller
{
    #[OA\Get(
        path: '/severity-levels',
        summary: 'List severity levels',
        description: 'Get all severity levels.',
        tags: ['Lookups']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function index(){
        $severityLevels = SeverityLevel::orderBy('severity_label')->get();

        return response()->json(['data' => $severityLevels]);
    }
}

