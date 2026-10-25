<?php

namespace Tests\Unit\Requests\AccommodationUnits;

use App\Domains\AccommodationUnits\Requests\UpdateAccommodationUnitRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateAccommodationUnitRequestTest extends TestCase
{
    private UpdateAccommodationUnitRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateAccommodationUnitRequest();
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

    public function test_validation_passes_with_empty_payload_because_all_are_sometimes(): void
    {
        $validator = Validator::make([], $this->request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_when_name_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'name' => str_repeat('B', 101),
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_when_type_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'type_id' => 9999999,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('type_id'));
    }

    public function test_validation_fails_when_max_capacity_is_less_than_one_or_invalid(): void
    {
        $validator = Validator::make([
            'max_capacity' => 0,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('max_capacity'));
    }

    public function test_validation_passes_with_valid_partial_update(): void
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
            'name' => 'Updated Room Name',
            'type_id' => $existingTypeId,
            'max_capacity' => 12,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
