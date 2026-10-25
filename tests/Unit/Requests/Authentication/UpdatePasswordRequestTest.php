<?php

namespace Tests\Unit\Requests\Authentication;

use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Requests\UpdatePasswordRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdatePasswordRequestTest extends TestCase
{
    private UpdatePasswordRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdatePasswordRequest();
    }

    public function test_it_denies_unauthenticated_user(): void
    {
        Auth::logout();
        $this->assertFalse($this->request->authorize());
    }

    public function test_it_authorizes_authenticated_user(): void
    {
        $user = new User();
        $user->user_id = 'AUTH-TEST-001';
        $this->actingAs($user);

        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('current_password', $rules);
        $this->assertArrayHasKey('new_password', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('current_password'));
        $this->assertTrue($validator->errors()->has('new_password'));
    }

    public function test_validation_fails_when_new_password_is_shorter_than_eight_chars(): void
    {
        $validator = Validator::make([
            'current_password'          => 'oldpass123',
            'new_password'              => 'short',
            'new_password_confirmation' => 'short',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('new_password'));
    }

    public function test_validation_fails_when_confirmation_does_not_match(): void
    {
        $validator = Validator::make([
            'current_password'          => 'oldpass123',
            'new_password'              => 'newSecurePass123',
            'new_password_confirmation' => 'differentPassword456',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('new_password'));
    }

    public function test_validation_passes_when_data_is_valid(): void
    {
        $validator = Validator::make([
            'current_password'          => 'oldpass123',
            'new_password'              => 'newSecurePass123',
            'new_password_confirmation' => 'newSecurePass123',
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
