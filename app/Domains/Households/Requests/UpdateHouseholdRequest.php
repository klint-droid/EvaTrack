<?php

namespace App\Domains\Households\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'household_name' => 'sometimes|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'barangay'       => 'nullable|string|max:255',
            'street'         => 'nullable|string|max:255',
            'purok'          => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'province'       => 'nullable|string|max:255',
            'full_address'   => 'nullable|string|max:500',
        ];
    }
}
