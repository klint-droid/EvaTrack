<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeverityLevel;

class SeverityLevelController extends Controller
{
    public function index(){
        $severityLevels = SeverityLevel::orderBy('severity_label')->get();

        return response()->json(['data' => $severityLevels]);
    }
}
