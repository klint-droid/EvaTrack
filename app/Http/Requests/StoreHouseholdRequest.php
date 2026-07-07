<?php

namespace App\Http\Requests;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;

class StoreHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'household_name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'address_id'     => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !Address::where('address_id', $value)->exists()) {
                        $fail('The selected address is invalid.');
                    }
                }
            ],
        ];
    }
}
