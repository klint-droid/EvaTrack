<?php

namespace App\Domains\EvacuationEvents\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignCentersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'center_id'   => 'required|array',
            'center_id.*' => 'exists:evacuation_centers,evacuation_center_id',
        ];
    }
}
