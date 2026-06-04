<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Sitio;
use App\Models\Purok;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AddressController extends Controller
{
    #[OA\Get(
        path: '/barangays',
        summary: 'List barangays',
        description: 'Get all barangays, optionally filtered by city.',
        tags: ['Addresses']
    )]
    #[OA\Parameter(name: 'city', in: 'query', description: 'Filter by city name', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
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

    #[OA\Get(
        path: '/barangays/{id}/sitios',
        summary: 'List sitios under a barangay',
        tags: ['Addresses']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Barangay ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    public function sitios($barangayId)
    {
        $sitios = Sitio::where('barangay_id', $barangayId)
            ->orderBy('sitio_name')
            ->get();

        return response()->json($sitios);
    }

    #[OA\Get(
        path: '/sitios/{id}/puroks',
        summary: 'List puroks under a sitio',
        tags: ['Addresses']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Sitio ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    public function puroks($sitioId)
    {
        $puroks = Purok::where('sitio_id', $sitioId)
            ->orderBy('purok_name')
            ->get();

        return response()->json($puroks);
    }
}

