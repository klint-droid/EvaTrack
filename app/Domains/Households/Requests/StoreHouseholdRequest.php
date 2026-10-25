<?php

namespace App\Domains\Households\Requests;

use App\Domains\ReferenceData\Models\Address;
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
            'barangay'                       => 'nullable|string|max:255',
            'street'                         => 'nullable|string|max:255',
            'purok'                          => 'nullable|string|max:255',
            'city'                           => 'nullable|string|max:255',
            'province'                       => 'nullable|string|max:255',
            'full_address'                   => 'nullable|string|max:500',
            'members'                        => 'nullable|array',
            'members.*.first_name'           => 'required_with:members|string|max:100',
            'members.*.middle_name'          => 'nullable|string|max:100',
            'members.*.last_name'            => 'required_with:members|string|max:100',
            'members.*.birth_date'           => 'required_with:members|date',
            'members.*.gender_id'            => 'nullable|integer',
            'members.*.gender'               => 'nullable|string|max:20',
            'members.*.sex'                  => 'nullable|string|max:1',
            'members.*.relationship_id'      => 'nullable|integer',
            'members.*.relation'             => 'nullable|string|max:50',
            'members.*.civil_status_id'      => 'nullable|integer',
            'members.*.civil_status'         => 'nullable|string|max:50',
            'members.*.is_household_head'    => 'nullable|boolean',
            'members.*.can_report_for_household' => 'nullable|boolean',
            'members.*.is_pwd'               => 'nullable|boolean',
            'members.*.is_pregnant'          => 'nullable|boolean',
            'members.*.vulnerable_group_ids' => 'nullable|array',
        ];
    }
}
