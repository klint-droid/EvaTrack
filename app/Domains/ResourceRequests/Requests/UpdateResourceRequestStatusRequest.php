<?php

namespace App\Domains\ResourceRequests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResourceRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,acknowledged,approved,rejected,delivered',
        ];
    }
}
