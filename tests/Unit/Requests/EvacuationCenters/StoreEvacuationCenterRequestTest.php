<?php

namespace Tests\Unit\Requests\EvacuationCenters;

use App\Domains\EvacuationCenters\Requests\StoreEvacuationCenterRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreEvacuationCenterRequestTest extends TestCase
{
    private StoreEvacuationCenterRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreEvacuationCenterRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('latitude', $rules);
        $this->assertArrayHasKey('longitude', $rules);
        $this->assertArrayHasKey('capacity', $rules);
        $this->assertArrayHasKey('osm_address', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('latitude'));
        $this->assertTrue($validator->errors()->has('longitude'));
        $this->assertTrue($validator->errors()->has('capacity'));
        $this->assertTrue($validator->errors()->has('osm_address'));
    }

    public function test_validation_fails_when_name_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'name'        => str_repeat('C', 101),
            'latitude'    => 10.3157,
            'longitude'   => 123.8854,
            'capacity'    => 500,
            'osm_address' => 'Cebu City, Philippines',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_when_coordinates_are_not_numeric(): void
    {
        $validator = Validator::make([
            'name'        => 'Central Gymnasium',
            'latitude'    => 'not-a-latitude',
            'longitude'   => 'not-a-longitude',
            'capacity'    => 500,
            'osm_address' => 'Cebu City, Philippines',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('latitude'));
        $this->assertTrue($validator->errors()->has('longitude'));
    }

    public function test_validation_fails_when_capacity_is_below_minimum_or_not_integer(): void
    {
        $validatorZero = Validator::make([
            'name'        => 'Central Gymnasium',
            'latitude'    => 10.3157,
            'longitude'   => 123.8854,
            'capacity'    => 0,
            'osm_address' => 'Cebu City',
        ], $this->request->rules());
        $this->assertTrue($validatorZero->fails());
        $this->assertTrue($validatorZero->errors()->has('capacity'));

        $validatorString = Validator::make([
            'name'        => 'Central Gymnasium',
            'latitude'    => 10.3157,
            'longitude'   => 123.8854,
            'capacity'    => 'five-hundred',
            'osm_address' => 'Cebu City',
        ], $this->request->rules());
        $this->assertTrue($validatorString->fails());
        $this->assertTrue($validatorString->errors()->has('capacity'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $validator = Validator::make([
            'name'        => 'Mambaling Evacuation Center',
            'latitude'    => 10.2974,
            'longitude'   => 123.8722,
            'capacity'    => 250,
            'osm_address' => 'Mambaling, Cebu City, Philippines',
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
