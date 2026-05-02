<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Sitio;
use App\Models\Purok;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function barangays(Request $request)
    {
        $query = Barangay::orderBy('barangay_name');

        if ($request->filled('city')) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('city_name', 'like', '%' . $request->city . '%');
            });
        }

        return response()->json($query->get());
    }

    public function sitios($barangayId)
    {
        $sitios = Sitio::where('barangay_id', $barangayId)
            ->orderBy('sitio_name')
            ->get();

        return response()->json($sitios);
    }

    public function puroks($sitioId)
    {
        $puroks = Purok::where('sitio_id', $sitioId)
            ->orderBy('purok_name')
            ->get();

        return response()->json($puroks);
    }
}
