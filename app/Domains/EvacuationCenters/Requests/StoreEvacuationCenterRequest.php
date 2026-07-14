<?php

namespace App\Domains\EvacuationCenters\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvacuationCenterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'name'              => 'required|string|max:100',
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
            'capacity'          => 'required|integer|min:1',
            'osm_address'       => 'required|string',
        ];
    }
}
