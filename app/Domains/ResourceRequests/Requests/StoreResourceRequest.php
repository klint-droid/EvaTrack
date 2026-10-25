<?php

namespace App\Domains\ResourceRequests\Requests;

use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
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
        ];
    }
}
