<?php

namespace App\Http\Requests;

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

            'street_address'    => 'nullable|string|max:200',
            'region_id'         => 'nullable|exists:regions,region_id',
            'province_id'       => 'nullable|exists:provinces,province_id',
            'city_id'           => 'nullable|exists:cities,city_id',
            'barangay_id'       => 'nullable|exists:barangays,barangay_id',
            'sitio_id'          => 'nullable|exists:sitios,sitio_id',
            'purok_id'          => 'nullable|exists:puroks,purok_id',
            'zipcode_id'        => 'nullable|exists:zipcodes,zipcode_id',
        ];
    }
}
