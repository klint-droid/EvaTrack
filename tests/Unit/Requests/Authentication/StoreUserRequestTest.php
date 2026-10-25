<?php

namespace Tests\Unit\Requests\Authentication;

use App\Domains\Authentication\Requests\StoreUserRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreUserRequestTest extends TestCase
{
    private StoreUserRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreUserRequest();
    }

    public function test_it_authorizes_super_admin(): void
    {
        $superAdmin = new \App\Domains\Authentication\Models\User(['role_id' => \App\Domains\Authentication\Models\User::ROLE_SUPER_ADMIN_ID]);
        $this->request->setUserResolver(fn () => $superAdmin);
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_denies_evac_admin(): void
    {
        $evacAdmin = new \App\Domains\Authentication\Models\User(['role_id' => \App\Domains\Authentication\Models\User::ROLE_EVAC_ADMIN_ID]);
        $this->request->setUserResolver(fn () => $evacAdmin);
        $this->assertFalse($this->request->authorize());
    }

    public function test_it_denies_unauthenticated_user(): void
    {
        $this->request->setUserResolver(fn () => null);
        $this->assertFalse($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('last_name', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('role', $rules);
        $this->assertArrayHasKey('contact_number', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('first_name'));
        $this->assertTrue($validator->errors()->has('last_name'));
        $this->assertTrue($validator->errors()->has('password'));
        $this->assertTrue($validator->errors()->has('contact_number'));
    }

    public function test_validation_fails_when_password_is_too_short(): void
    {
        $validator = Validator::make([
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'password'       => 'short',
            'contact_number' => '09991234567',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('password'));
    }

    public function test_validation_fails_when_role_is_invalid(): void
    {
        $validator = Validator::make([
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'password'       => 'password123',
            'contact_number' => '09991234567',
            'role'           => 'invalid_role',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('role'));
    }

    public function test_validation_fails_when_contact_number_is_not_unique(): void
    {
        $existingContact = DB::connection('mysql_v2')->table('users')->whereNotNull('contact_number')->value('contact_number');
        if (!$existingContact) {
            $existingContact = '09876543210';
            DB::connection('mysql_v2')->table('users')->insert([
                'user_id' => 'TEST-001',
                'first_name' => 'Existing',
                'last_name' => 'User',
                'contact_number' => $existingContact,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validator = Validator::make([
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'password'       => 'password123',
            'contact_number' => $existingContact,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('contact_number'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $uniqueContact = '09' . rand(100000000, 999999999);

        $validator = Validator::make([
            'first_name'     => 'Unique',
            'last_name'      => 'User',
            'password'       => 'securePassword123',
            'role'           => 'evac_personnel',
            'contact_number' => $uniqueContact,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
