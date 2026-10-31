<?php

namespace App\Domains\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateUserRequest
 *
 * Adheres to Interface Segregation (ISP) and Single Responsibility (SRP).
 * Validates incoming HTTP payloads for updating an existing user.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via route middleware: role:super_admin,evac_admin
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('user');

        return [
            'first_name'     => 'sometimes|string|max:255',
            'last_name'      => 'sometimes|string|max:255',
            'email'          => 'nullable|email|max:255|unique:users,email,' . $userId . ',user_id',
            'role'           => 'sometimes|in:evac_personnel,evac_admin,super_admin',
            'contact_number' => 'nullable|string|unique:users,contact_number,' . $userId . ',user_id',
        ];
    }
}
