<?php

namespace Tests\Unit\Requests\EvacuationCenters;

use App\Domains\EvacuationCenters\Requests\UpdateEvacuationCenterRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateEvacuationCenterRequestTest extends TestCase
{
    private UpdateEvacuationCenterRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateEvacuationCenterRequest();
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

    public function test_validation_fails_when_coordinates_are_not_numeric(): void
    {
        $validator = Validator::make([
            'name'        => 'Updated Center',
            'latitude'    => 'invalid-lat',
            'longitude'   => 'invalid-lng',
            'capacity'    => 100,
            'osm_address' => 'Sample Address',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('latitude'));
        $this->assertTrue($validator->errors()->has('longitude'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $validator = Validator::make([
            'name'        => 'Updated Center Name',
            'latitude'    => 10.3157,
            'longitude'   => 123.8854,
            'capacity'    => 400,
            'osm_address' => 'Updated Osm Address',
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
