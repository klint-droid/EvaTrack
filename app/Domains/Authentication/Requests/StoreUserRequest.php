<?php

namespace App\Domains\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreUserRequest
 *
 * Adheres to Interface Segregation (ISP) and Single Responsibility (SRP).
 * Validates incoming HTTP payloads for creating a new user.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'password'       => 'required|string|min:8',
            'role'           => 'nullable|in:evac_personnel,evac_admin,super_admin',
            'contact_number' => 'required|string|unique:users,contact_number',
        ];
    }
}
