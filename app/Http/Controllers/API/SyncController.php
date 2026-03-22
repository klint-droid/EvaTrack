<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ExternalApiService;
use App\Models\Household;

class SyncController extends Controller
{
    public function syncHouseholds(ExternalApiService $api){
        $api->syncHouseholds();
        return response()->json(['message' => 'Households synced successfully']);
    }
}
