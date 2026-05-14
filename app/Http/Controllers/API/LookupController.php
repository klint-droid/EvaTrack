<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gender;
use App\Models\Relationship;
use App\Models\CivilStatus;
use App\Models\VulnerableGroup;

class LookupController extends Controller
{
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
