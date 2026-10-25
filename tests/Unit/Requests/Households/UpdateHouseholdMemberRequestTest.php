<?php

namespace Tests\Unit\Requests\Households;

use App\Domains\Households\Requests\UpdateHouseholdMemberRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateHouseholdMemberRequestTest extends TestCase
{
    private UpdateHouseholdMemberRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateHouseholdMemberRequest();
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
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('first_name'));
        $this->assertTrue($validator->errors()->has('last_name'));
        $this->assertTrue($validator->errors()->has('birth_date'));
    }

    public function test_validation_fails_when_birth_date_is_invalid(): void
    {
        $validator = Validator::make([
            'first_name' => 'Pedro',
            'last_name'  => 'Penduko',
            'birth_date' => '32-13-2020',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('birth_date'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $validator = Validator::make([
            'first_name'           => 'Pedro',
            'middle_name'          => 'Gomez',
            'last_name'            => 'Penduko',
            'birth_date'           => '1985-11-20',
            'gender_id'            => 1,
            'relationship_id'      => 2,
            'civil_status_id'      => 1,
            'vulnerable_group_ids' => [],
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
