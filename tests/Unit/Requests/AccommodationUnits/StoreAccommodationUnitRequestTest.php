<?php

namespace Tests\Unit\Requests\AccommodationUnits;

use App\Domains\AccommodationUnits\Requests\StoreAccommodationUnitRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreAccommodationUnitRequestTest extends TestCase
{
    private StoreAccommodationUnitRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreAccommodationUnitRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('type_id', $rules);
        $this->assertArrayHasKey('max_capacity', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('type_id'));
        $this->assertTrue($validator->errors()->has('max_capacity'));
    }

    public function test_validation_fails_when_name_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'name'         => str_repeat('A', 101),
            'type_id'      => 1,
            'max_capacity' => 5,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_when_type_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'name'         => 'Room A',
            'type_id'      => 999999,
            'max_capacity' => 5,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('type_id'));
    }

    public function test_validation_fails_when_max_capacity_is_less_than_one_or_not_integer(): void
    {
        $validatorZero = Validator::make([
            'name'         => 'Room A',
            'type_id'      => 1,
            'max_capacity' => 0,
        ], $this->request->rules());
        $this->assertTrue($validatorZero->fails());
        $this->assertTrue($validatorZero->errors()->has('max_capacity'));

        $validatorString = Validator::make([
            'name'         => 'Room A',
            'type_id'      => 1,
            'max_capacity' => 'not-an-int',
        ], $this->request->rules());
        $this->assertTrue($validatorString->fails());
        $this->assertTrue($validatorString->errors()->has('max_capacity'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $existingTypeId = DB::connection('mysql_v2')->table('accommodation_types')->value('type_id');
        if (!$existingTypeId) {
            $existingTypeId = DB::connection('mysql_v2')->table('accommodation_types')->insertGetId([
                'name' => 'Standard Unit',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validator = Validator::make([
            'name'         => 'Tent 101',
            'type_id'      => $existingTypeId,
            'max_capacity' => 8,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
