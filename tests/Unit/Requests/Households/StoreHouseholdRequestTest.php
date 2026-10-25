<?php

namespace Tests\Unit\Requests\Households;

use App\Domains\Households\Requests\StoreHouseholdRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreHouseholdRequestTest extends TestCase
{
    private StoreHouseholdRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreHouseholdRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('household_name', $rules);
        $this->assertArrayHasKey('contact_number', $rules);
        $this->assertArrayHasKey('address_id', $rules);
        $this->assertArrayHasKey('barangay', $rules);
        $this->assertArrayHasKey('members', $rules);
        $this->assertArrayHasKey('members.*.first_name', $rules);
        $this->assertArrayHasKey('members.*.last_name', $rules);
        $this->assertArrayHasKey('members.*.birth_date', $rules);
    }

    public function test_validation_fails_when_household_name_is_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_name'));
    }

    public function test_validation_fails_when_household_name_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'household_name' => str_repeat('H', 256),
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_name'));
    }

    public function test_validation_fails_when_address_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'household_name' => 'Dela Cruz Family',
            'address_id'     => 99999999,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('address_id'));
    }

    public function test_validation_fails_when_member_is_missing_required_fields(): void
    {
        $validator = Validator::make([
            'household_name' => 'Santos Family',
            'members'        => [
                [
                    // missing first_name, last_name, birth_date
                    'middle_name' => 'Test',
                ],
            ],
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('members.0.first_name'));
        $this->assertTrue($validator->errors()->has('members.0.last_name'));
        $this->assertTrue($validator->errors()->has('members.0.birth_date'));
    }

    public function test_validation_passes_with_valid_household_without_members(): void
    {
        $validator = Validator::make([
            'household_name' => 'Reyes Family',
            'contact_number' => '09123456789',
            'barangay'       => 'Poblacion',
            'city'           => 'Cebu City',
            'province'       => 'Cebu',
            'full_address'   => '123 Main St, Poblacion, Cebu City',
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_validation_passes_with_valid_household_and_members(): void
    {
        $validator = Validator::make([
            'household_name' => 'Tan Family',
            'contact_number' => '09223344556',
            'barangay'       => 'Mambaling',
            'members'        => [
                [
                    'first_name'        => 'Roberto',
                    'last_name'         => 'Tan',
                    'birth_date'        => '1980-04-12',
                    'gender'            => 'Male',
                    'is_household_head' => true,
                ],
                [
                    'first_name'        => 'Elena',
                    'last_name'         => 'Tan',
                    'birth_date'        => '1982-08-25',
                    'gender'            => 'Female',
                    'is_household_head' => false,
                ],
            ],
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
