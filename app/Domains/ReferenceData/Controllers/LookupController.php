<?php

namespace App\Domains\ReferenceData\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domains\ReferenceData\Models\Gender;
use App\Domains\ReferenceData\Models\Relationship;
use App\Domains\ReferenceData\Models\CivilStatus;
use App\Domains\ReferenceData\Models\VulnerableGroup;
use OpenApi\Attributes as OA;

class LookupController extends Controller
{
    #[OA\Get(
        path: '/lookups',
        summary: 'Get system lookups',
        description: 'Returns list of genders, relationships, civil statuses, and vulnerable groups.',
        security: [['bearerAuth' => []]],
        tags: ['Lookups']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index()
    {
        return response()->json([
            'genders' => Gender::select('gender_id as id', 'gender_label as label')->get(),

            'relationships' => Relationship::select('relationship_id as id', 'relationship_label as label')->get(),

            'civil_statuses' => CivilStatus::select('status_id as id', 'status_label as label')->get(),

            'vulnerable_groups' => VulnerableGroup::select('vulnerable_group_id as id', 'vulnerable_group_label as label')->get(),
        ]);
    }
}

