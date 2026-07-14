<?php

namespace App\Domains\EvacuationEvents\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisasterEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:100',
            'type_id'     => 'required|exists:disaster_types,type_id',
            'severity_id' => 'required|exists:severity_levels,severity_id',
        ];
    }
}
