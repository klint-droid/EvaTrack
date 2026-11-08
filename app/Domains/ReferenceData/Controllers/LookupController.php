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
        if (Gender::count() === 0) {
            Gender::upsert([
                ['gender_id' => 1, 'gender_key' => 'male',   'gender_label' => 'Male'],
                ['gender_id' => 2, 'gender_key' => 'female', 'gender_label' => 'Female'],
                ['gender_id' => 3, 'gender_key' => 'other',  'gender_label' => 'Other'],
            ], ['gender_id'], ['gender_key', 'gender_label']);
        }

        if (Relationship::count() === 0) {
            Relationship::upsert([
                ['relationship_id' => 1, 'relationship_key' => 'head',    'relationship_label' => 'Head of Household'],
                ['relationship_id' => 2, 'relationship_key' => 'spouse',  'relationship_label' => 'Spouse'],
                ['relationship_id' => 3, 'relationship_key' => 'child',   'relationship_label' => 'Child'],
                ['relationship_id' => 4, 'relationship_key' => 'parent',  'relationship_label' => 'Parent'],
                ['relationship_id' => 5, 'relationship_key' => 'sibling', 'relationship_label' => 'Sibling'],
                ['relationship_id' => 6, 'relationship_key' => 'other',   'relationship_label' => 'Other Relative'],
            ], ['relationship_id'], ['relationship_key', 'relationship_label']);
        }

        if (CivilStatus::count() === 0) {
            CivilStatus::upsert([
                ['status_id' => 1, 'status_key' => 'single',    'status_label' => 'Single'],
                ['status_id' => 2, 'status_key' => 'married',   'status_label' => 'Married'],
                ['status_id' => 3, 'status_key' => 'widowed',   'status_label' => 'Widowed'],
                ['status_id' => 4, 'status_key' => 'separated', 'status_label' => 'Separated'],
                ['status_id' => 5, 'status_key' => 'divorced',  'status_label' => 'Divorced'],
                ['status_id' => 6, 'status_key' => 'annulled',  'status_label' => 'Annulled'],
            ], ['status_id'], ['status_key', 'status_label']);
        }

        if (VulnerableGroup::count() === 0) {
            VulnerableGroup::upsert([
                ['vulnerable_group_id' => 1, 'vulnerable_group_key' => 'elderly',    'vulnerable_group_label' => 'Elderly'],
                ['vulnerable_group_id' => 2, 'vulnerable_group_key' => 'children',   'vulnerable_group_label' => 'Children'],
                ['vulnerable_group_id' => 3, 'vulnerable_group_key' => 'pwd',        'vulnerable_group_label' => 'PWD'],
                ['vulnerable_group_id' => 4, 'vulnerable_group_key' => 'pregnant',   'vulnerable_group_label' => 'Pregnant Women'],
                ['vulnerable_group_id' => 5, 'vulnerable_group_key' => 'indigenous', 'vulnerable_group_label' => 'Indigenous People'],
            ], ['vulnerable_group_id'], ['vulnerable_group_key', 'vulnerable_group_label']);
        }

        return response()->json([
            'genders' => Gender::select('gender_id as id', 'gender_label as label')->get(),

            'relationships' => Relationship::select('relationship_id as id', 'relationship_label as label')->get(),

            'civil_statuses' => CivilStatus::select('status_id as id', 'status_label as label')->get(),

            'vulnerable_groups' => VulnerableGroup::select('vulnerable_group_id as id', 'vulnerable_group_label as label')->get(),
        ]);
    }
}

