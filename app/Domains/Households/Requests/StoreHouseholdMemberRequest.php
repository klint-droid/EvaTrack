<?php

namespace App\Domains\Households\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHouseholdMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'           => 'required|string|max:100',
            'middle_name'          => 'nullable|string|max:100',
            'last_name'            => 'required|string|max:100',
            'birth_date'           => 'required|date',
            'gender_id'            => 'nullable|integer',
            'relationship_id'      => 'nullable|integer',
            'civil_status_id'      => 'nullable|integer',
            'vulnerable_group_ids' => 'nullable|array',
            'vulnerable_group_ids.*' => 'integer',
        ];
    }
}
