<?php

namespace Tests\Unit\Requests\Authentication;

use App\Domains\Authentication\Requests\UpdateUserRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateUserRequestTest extends TestCase
{
    private UpdateUserRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateUserRequest();
        $route = new Route('PUT', 'api/users/{id}', []);
        $route->parameters = ['id' => 'USR-999'];
        $this->request->setRouteResolver(fn() => $route);
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('last_name', $rules);
        $this->assertArrayHasKey('role', $rules);
        $this->assertArrayHasKey('contact_number', $rules);
    }

    public function test_validation_passes_with_empty_payload(): void
    {
        $validator = Validator::make([], $this->request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_when_role_is_invalid(): void
    {
        $validator = Validator::make([
            'role' => 'invalid_role_type',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('role'));
    }

    public function test_validation_fails_when_first_name_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'first_name' => str_repeat('X', 256),
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('first_name'));
    }

    public function test_validation_passes_with_valid_partial_update(): void
    {
        $validator = Validator::make([
            'first_name'     => 'UpdatedName',
            'role'           => 'evac_admin',
            'contact_number' => '09' . rand(100000000, 999999999),
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
