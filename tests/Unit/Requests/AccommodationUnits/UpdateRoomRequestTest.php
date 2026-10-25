<?php

namespace Tests\Unit\Requests\AccommodationUnits;

use App\Domains\AccommodationUnits\Requests\UpdateRoomRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateRoomRequestTest extends TestCase
{
    private UpdateRoomRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateRoomRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('evacuation_center_id', $rules);
        $this->assertArrayHasKey('room_number', $rules);
        $this->assertArrayHasKey('max_capacity', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('evacuation_center_id'));
        $this->assertTrue($validator->errors()->has('room_number'));
        $this->assertTrue($validator->errors()->has('max_capacity'));
    }

    public function test_validation_fails_when_evacuation_center_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'evacuation_center_id' => 9999999,
            'room_number'          => 'RM-101',
            'max_capacity'         => 10,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('evacuation_center_id'));
    }

    public function test_validation_fails_when_room_number_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'evacuation_center_id' => 1,
            'room_number'          => str_repeat('R', 21),
            'max_capacity'         => 10,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('room_number'));
    }

    public function test_validation_fails_when_max_capacity_is_less_than_one(): void
    {
        $validator = Validator::make([
            'evacuation_center_id' => 1,
            'room_number'          => 'RM-101',
            'max_capacity'         => 0,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('max_capacity'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->value('evacuation_center_id');
        if (!$centerId) {
            $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->insertGetId([
                'name' => 'Test Center',
                'latitude' => 10.123,
                'longitude' => 123.456,
                'capacity' => 100,
                'osm_address' => 'Test Address',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validator = Validator::make([
            'evacuation_center_id' => $centerId,
            'room_number'          => 'RM-101',
            'max_capacity'         => 15,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
