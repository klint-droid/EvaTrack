<?php

namespace App\Http\Requests;

use App\Models\EvacuationCenter;
use App\Models\UrgencyLevel;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evacuation_center_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !EvacuationCenter::where('evacuation_center_id', $value)->exists()) {
                        $fail('Evacuation center does not exist.');
                    }
                }
            ],
            'resource_type' => 'required|string|max:100',
            'quantity'      => 'required|integer|min:1',
            'description'   => 'nullable|string',
            'urgency_id'    => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!UrgencyLevel::where('urgency_id', $value)->exists()) {
                        $fail('The selected urgency level is invalid.');
                    }
                }
            ],
        ];
    }
}
