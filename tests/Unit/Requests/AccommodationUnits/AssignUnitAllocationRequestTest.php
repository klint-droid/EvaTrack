<?php

namespace Tests\Unit\Requests\AccommodationUnits;

use App\Domains\AccommodationUnits\Requests\AssignUnitAllocationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AssignUnitAllocationRequestTest extends TestCase
{
    private AssignUnitAllocationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new AssignUnitAllocationRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('evacuation_id', $rules);
    }

    public function test_validation_fails_when_evacuation_id_is_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('evacuation_id'));
    }

    public function test_validation_fails_when_evacuation_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'evacuation_id' => 99999999,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('evacuation_id'));
    }

    public function test_validation_passes_when_evacuation_id_exists(): void
    {
        $evacuationId = DB::connection('mysql_v2')->table('evacuation_records')->value('evacuation_id');
        if (!$evacuationId) {
            $evacuationId = DB::connection('mysql_v2')->table('evacuation_records')->insertGetId([
                'evacuated_count' => 3,
                'method' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validator = Validator::make([
            'evacuation_id' => $evacuationId,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
