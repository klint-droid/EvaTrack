<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DisasterType;

class DisasterTypeController extends Controller
{
    public function index(){
        $disasterTypes = DisasterType::query()
            ->where('is_active', true)
            ->orderBy('type_name')
            ->get();

        return response()->json(['data' => $disasterTypes]);
    }
}
