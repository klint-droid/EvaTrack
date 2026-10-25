<?php

namespace Tests\Unit\Requests\Authentication;

use App\Domains\Authentication\Requests\AssignCenterRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AssignCenterRequestTest extends TestCase
{
    private AssignCenterRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new AssignCenterRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('assigned_center_id', $rules);
    }

    public function test_validation_passes_when_assigned_center_id_is_null_or_omitted(): void
    {
        $validatorEmpty = Validator::make([], $this->request->rules());
        $this->assertFalse($validatorEmpty->fails());

        $validatorNull = Validator::make(['assigned_center_id' => null], $this->request->rules());
        $this->assertFalse($validatorNull->fails());
    }

    public function test_validation_fails_when_assigned_center_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'assigned_center_id' => 99999999,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('assigned_center_id'));
    }

    public function test_validation_passes_when_assigned_center_id_exists(): void
    {
        $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->value('evacuation_center_id');
        if (!$centerId) {
            $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->insertGetId([
                'name' => 'Center Auth Test',
                'latitude' => 10.123,
                'longitude' => 123.456,
                'capacity' => 100,
                'osm_address' => 'Test Address',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validator = Validator::make([
            'assigned_center_id' => $centerId,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
