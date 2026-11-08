<?php

namespace App\Domains\EvacuationEvents\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domains\EvacuationEvents\Models\SeverityLevel;
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
        if (SeverityLevel::count() === 0) {
            SeverityLevel::upsert([
                ['severity_id' => 1, 'severity_key' => 'low',      'severity_label' => 'Low'],
                ['severity_id' => 2, 'severity_key' => 'medium',   'severity_label' => 'Medium'],
                ['severity_id' => 3, 'severity_key' => 'high',     'severity_label' => 'High'],
                ['severity_id' => 4, 'severity_key' => 'critical', 'severity_label' => 'Critical'],
            ], ['severity_id'], ['severity_key', 'severity_label']);
        }

        $severityLevels = SeverityLevel::orderBy('severity_id')->get();

        return response()->json(['data' => $severityLevels]);
    }
}

