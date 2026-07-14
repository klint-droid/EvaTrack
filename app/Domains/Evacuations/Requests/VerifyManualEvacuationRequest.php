<?php

namespace App\Domains\Evacuations\Requests;

use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\Households\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class VerifyManualEvacuationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'member_ids.*' => 'exists:household_members,member_id',
        ];
    }
}
