<?php

namespace App\Domains\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Class UpdateProfileRequest
 *
 * Adheres to Interface Segregation (ISP) and Single Responsibility (SRP).
 * Validates self-profile update requests.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'email'          => 'nullable|email|max:255|unique:users,email,' . $userId . ',user_id',
            'contact_number' => 'nullable|string|unique:users,contact_number,' . $userId . ',user_id',
            'profile_photo'  => 'nullable|image|max:5120',
        ];
    }
}
