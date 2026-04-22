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
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',

            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',

            'capacity' => 'required|integer|min:1',

            'region' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'purok' => 'nullable|string|max:100',
            'full_address' => 'nullable|string|max:255',
        ];
    }
}
