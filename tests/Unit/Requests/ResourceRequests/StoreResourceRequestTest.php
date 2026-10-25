<?php

namespace Tests\Unit\Requests\ResourceRequests;

use App\Domains\ResourceRequests\Requests\StoreResourceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreResourceRequestTest extends TestCase
{
    private StoreResourceRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreResourceRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('evacuation_center_id', $rules);
        $this->assertArrayHasKey('resource_type', $rules);
        $this->assertArrayHasKey('quantity', $rules);
        $this->assertArrayHasKey('description', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('resource_type'));
        $this->assertTrue($validator->errors()->has('quantity'));
    }

    public function test_validation_fails_when_resource_type_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'resource_type' => str_repeat('R', 101),
            'quantity'      => 10,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('resource_type'));
    }

    public function test_validation_fails_when_quantity_is_less_than_one_or_not_integer(): void
    {
        $validatorZero = Validator::make([
            'resource_type' => 'Bottled Water',
            'quantity'      => 0,
        ], $this->request->rules());
        $this->assertTrue($validatorZero->fails());
        $this->assertTrue($validatorZero->errors()->has('quantity'));

        $validatorString = Validator::make([
            'resource_type' => 'Bottled Water',
            'quantity'      => 'ten_boxes',
        ], $this->request->rules());
        $this->assertTrue($validatorString->fails());
        $this->assertTrue($validatorString->errors()->has('quantity'));
    }

    public function test_validation_fails_when_evacuation_center_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'evacuation_center_id' => 99999999,
            'resource_type'        => 'Medical Kits',
            'quantity'             => 5,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('evacuation_center_id'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->value('evacuation_center_id');

        $validator = Validator::make([
            'evacuation_center_id' => $centerId,
            'resource_type'        => 'Food Packs',
            'quantity'             => 100,
            'description'          => 'Emergency family food packs needed.',
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
