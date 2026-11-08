<?php

namespace App\Domains\Evacuations\Requests;

use App\Domains\Households\Models\Household;
use App\Domains\Households\Models\HouseholdMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvacuationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canVerifyHouseholds() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'household_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Household::where('household_id', $value)->exists()) {
                        $fail('The selected household is invalid.');
                    }
                },
            ],
            'member_ids'   => 'nullable|array',
            'member_ids.*' => [
                function ($attribute, $value, $fail) {
                    if ($value && !HouseholdMember::where('member_id', $value)->exists()) {
                        $fail('One or more selected household members are invalid.');
                    }
                },
            ],
            'unit_id'      => 'nullable|integer',
        ];
    }
}
