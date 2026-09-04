<?php

namespace App\Domains\ResourceRequests\Requests;

use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Notifications\Models\UrgencyLevel;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if (!$this->urgency_id) {
            $defaultUrgency = UrgencyLevel::where('urgency_key', 'medium')->value('urgency_id') 
                ?? UrgencyLevel::first()?->urgency_id ?? 1;
            $this->merge(['urgency_id' => $defaultUrgency]);
        }
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
