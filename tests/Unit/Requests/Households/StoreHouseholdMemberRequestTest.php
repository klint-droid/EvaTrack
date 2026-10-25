<?php

namespace Tests\Unit\Requests\Households;

use App\Domains\Households\Requests\StoreHouseholdMemberRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreHouseholdMemberRequestTest extends TestCase
{
    private StoreHouseholdMemberRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreHouseholdMemberRequest();
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
        $this->assertArrayHasKey('birth_date', $rules);
        $this->assertArrayHasKey('gender_id', $rules);
        $this->assertArrayHasKey('relationship_id', $rules);
        $this->assertArrayHasKey('civil_status_id', $rules);
        $this->assertArrayHasKey('vulnerable_group_ids', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('first_name'));
        $this->assertTrue($validator->errors()->has('last_name'));
        $this->assertTrue($validator->errors()->has('birth_date'));
    }

    public function test_validation_fails_when_names_exceed_max_length(): void
    {
        $validator = Validator::make([
            'first_name' => str_repeat('N', 101),
            'last_name'  => str_repeat('L', 101),
            'birth_date' => '1995-05-15',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('first_name'));
        $this->assertTrue($validator->errors()->has('last_name'));
    }

    public function test_validation_fails_when_birth_date_is_invalid(): void
    {
        $validator = Validator::make([
            'first_name' => 'Maria',
            'last_name'  => 'Santos',
            'birth_date' => 'not-a-valid-date',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('birth_date'));
    }

    public function test_validation_fails_when_vulnerable_group_ids_contains_non_integers(): void
    {
        $validator = Validator::make([
            'first_name'           => 'Maria',
            'last_name'            => 'Santos',
            'birth_date'           => '1995-05-15',
            'vulnerable_group_ids' => ['string_instead_of_int'],
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('vulnerable_group_ids.0'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $validator = Validator::make([
            'first_name'           => 'Juan',
            'middle_name'          => 'Cruz',
            'last_name'            => 'Dela Cruz',
            'birth_date'           => '1990-01-01',
            'gender_id'            => 1,
            'relationship_id'      => 1,
            'civil_status_id'      => 1,
            'vulnerable_group_ids' => [1, 2],
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
