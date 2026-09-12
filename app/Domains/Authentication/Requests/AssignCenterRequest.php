<?php

namespace App\Domains\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AssignCenterRequest
 *
 * Adheres to Interface Segregation (ISP) and Single Responsibility (SRP).
 * Validates center assignment payload for a user.
 */
class AssignCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_center_id' => 'nullable|exists:evacuation_centers,evacuation_center_id',
        ];
    }
}
