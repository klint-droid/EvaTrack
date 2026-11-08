<?php

namespace App\Domains\AccommodationUnits\Requests;

use App\Domains\Evacuations\Models\EvacuationRecord;
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
            'evacuation_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!EvacuationRecord::where('evacuation_id', $value)->exists()) {
                        $fail('The selected evacuation record is invalid.');
                    }
                },
            ],
        ];
    }
}
