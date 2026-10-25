<?php

namespace Tests\Unit\Requests\Households;

use App\Domains\Households\Requests\UpdateHouseholdRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateHouseholdRequestTest extends TestCase
{
    private UpdateHouseholdRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateHouseholdRequest();
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
        $this->assertArrayHasKey('barangay', $rules);
        $this->assertArrayHasKey('street', $rules);
        $this->assertArrayHasKey('purok', $rules);
        $this->assertArrayHasKey('city', $rules);
        $this->assertArrayHasKey('province', $rules);
        $this->assertArrayHasKey('full_address', $rules);
    }

    public function test_validation_passes_with_empty_payload(): void
    {
        $validator = Validator::make([], $this->request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_when_household_name_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'household_name' => str_repeat('H', 256),
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_name'));
    }

    public function test_validation_fails_when_contact_number_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'contact_number' => str_repeat('9', 51),
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('contact_number'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $validator = Validator::make([
            'household_name' => 'Updated Dela Cruz Family',
            'contact_number' => '09987654321',
            'barangay'       => 'Guadalupe',
            'city'           => 'Cebu City',
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
