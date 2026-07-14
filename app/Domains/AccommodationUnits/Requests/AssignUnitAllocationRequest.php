<?php

namespace App\Domains\AccommodationUnits\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUnitAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evacuation_id' => 'required|exists:evacuation_records,evacuation_id',
        ];
    }
}
