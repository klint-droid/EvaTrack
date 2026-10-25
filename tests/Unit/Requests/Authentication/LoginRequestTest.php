<?php

namespace Tests\Unit\Requests\Authentication;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    private array $rules = [
        'user_id'  => 'required|string',
        'password' => 'required|string',
    ];

    public function test_validation_fails_when_user_id_or_password_is_missing(): void
    {
        $validator = Validator::make([], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('user_id'));
        $this->assertTrue($validator->errors()->has('password'));
    }

    public function test_validation_fails_when_inputs_are_not_strings(): void
    {
        $validator = Validator::make([
            'user_id'  => ['array_instead_of_string'],
            'password' => 12345, // string rule in Laravel allows numeric scalars, but array fails
        ], [
            'user_id'  => 'required|string',
            'password' => 'required|string',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('user_id'));
    }

    public function test_validation_passes_when_credentials_are_provided(): void
    {
        $validator = Validator::make([
            'user_id'  => 'SUP-2026-ABCDEF',
            'password' => 'supersecret',
        ], $this->rules);

        $this->assertFalse($validator->fails());
    }
}
