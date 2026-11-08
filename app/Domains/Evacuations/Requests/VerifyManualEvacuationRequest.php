<?php

namespace App\Domains\Evacuations\Requests;

use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\Households\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class VerifyManualEvacuationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canVerifyHouseholds() ?? false;
    }

    public function rules(): array
    {
        return [
            'household_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Household::where('household_id', $value)->exists()) {
                        $fail('The selected household is invalid.');
                    }
                }
            ],
            'event_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !DisasterEvent::where('event_id', $value)->exists()) {
                        $fail('The selected event is invalid.');
                    }
                }
            ],
            'member_count' => 'nullable|integer|min:1',
            'member_ids'   => 'nullable|array',
            'member_ids.*' => [
                function ($attribute, $value, $fail) {
                    if ($value && !\App\Domains\Households\Models\HouseholdMember::where('member_id', $value)->exists()) {
                        $fail('One or more selected household members are invalid.');
                    }
                },
            ],
            'unit_id'      => 'nullable|integer',
        ];
    }
}
