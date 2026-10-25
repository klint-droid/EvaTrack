<?php

namespace Tests\Unit\Requests\EvacuationEvents;

use App\Domains\EvacuationEvents\Requests\StoreDisasterEventRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreDisasterEventRequestTest extends TestCase
{
    private StoreDisasterEventRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreDisasterEventRequest();
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
        $this->assertArrayHasKey('severity_id', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('type_id'));
    }

    public function test_validation_fails_when_name_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'name'    => str_repeat('E', 101),
            'type_id' => 1,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_when_type_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'name'    => 'Typhoon Agaton',
            'type_id' => 9999999,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('type_id'));
    }

    public function test_validation_fails_when_severity_id_does_not_exist(): void
    {
        $typeId = DB::connection('mysql_v2')->table('disaster_types')->value('type_id');

        $validator = Validator::make([
            'name'        => 'Typhoon Kristine',
            'type_id'     => $typeId ?? 1,
            'severity_id' => 9999999,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('severity_id'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $typeId = DB::connection('mysql_v2')->table('disaster_types')->value('type_id');
        if (!$typeId) {
            $typeId = DB::connection('mysql_v2')->table('disaster_types')->insertGetId([
                'name' => 'Flood',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $severityId = DB::connection('mysql_v2')->table('severity_levels')->value('severity_id');

        $validator = Validator::make([
            'name'        => 'Typhoon Odette',
            'type_id'     => $typeId,
            'severity_id' => $severityId,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
