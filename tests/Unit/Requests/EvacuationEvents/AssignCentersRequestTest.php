<?php

namespace Tests\Unit\Requests\EvacuationEvents;

use App\Domains\EvacuationEvents\Requests\AssignCentersRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AssignCentersRequestTest extends TestCase
{
    private AssignCentersRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new AssignCentersRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('center_id', $rules);
        $this->assertArrayHasKey('center_id.*', $rules);
    }

    public function test_validation_fails_when_center_id_is_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('center_id'));
    }

    public function test_validation_fails_when_center_id_is_not_an_array(): void
    {
        $validator = Validator::make([
            'center_id' => 'single-center-id',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('center_id'));
    }

    public function test_validation_fails_when_any_center_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'center_id' => [99999999],
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('center_id.0'));
    }

    public function test_validation_passes_when_center_ids_exist(): void
    {
        $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->value('evacuation_center_id');
        if (!$centerId) {
            $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->insertGetId([
                'name' => 'Event Center',
                'latitude' => 10.123,
                'longitude' => 123.456,
                'capacity' => 100,
                'osm_address' => 'Test Address',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validator = Validator::make([
            'center_id' => [$centerId],
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
