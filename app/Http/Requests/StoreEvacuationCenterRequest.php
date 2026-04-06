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
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer',
            'has_rooms' => 'nullable|boolean',
            'room_count' => 'nullable|integer',
            'rooms' => 'nullable|array',

            'rooms.*.room_number' => 'required_with:rooms|string|max:50',
            'rooms.*.max_capacity' => 'required_with:rooms|integer',
        ];
    }
}
