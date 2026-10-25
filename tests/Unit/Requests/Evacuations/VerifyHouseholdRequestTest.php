<?php

namespace Tests\Unit\Requests\Evacuations;

use App\Domains\Evacuations\Requests\VerifyHouseholdRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VerifyHouseholdRequestTest extends TestCase
{
    private VerifyHouseholdRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new VerifyHouseholdRequest();
    }

    public function test_it_authorizes_evac_personnel(): void
    {
        $personnel = new \App\Domains\Authentication\Models\User(['role_id' => \App\Domains\Authentication\Models\User::ROLE_EVAC_PERSONNEL_ID]);
        $this->request->setUserResolver(fn () => $personnel);
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_denies_evac_admin(): void
    {
        $evacAdmin = new \App\Domains\Authentication\Models\User(['role_id' => \App\Domains\Authentication\Models\User::ROLE_EVAC_ADMIN_ID]);
        $this->request->setUserResolver(fn () => $evacAdmin);
        $this->assertFalse($this->request->authorize());
    }

    public function test_it_denies_super_admin(): void
    {
        $superAdmin = new \App\Domains\Authentication\Models\User(['role_id' => \App\Domains\Authentication\Models\User::ROLE_SUPER_ADMIN_ID]);
        $this->request->setUserResolver(fn () => $superAdmin);
        $this->assertFalse($this->request->authorize());
    }

    public function test_it_denies_unauthenticated_user(): void
    {
        $this->request->setUserResolver(fn () => null);
        $this->assertFalse($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('household_id', $rules);
        $this->assertArrayHasKey('evacuation_center_id', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_id'));
        $this->assertTrue($validator->errors()->has('evacuation_center_id'));
    }

    public function test_validation_fails_when_household_or_center_does_not_exist(): void
    {
        $validator = Validator::make([
            'household_id'         => 'NON-EXISTENT-HH',
            'evacuation_center_id' => 99999999,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_id'));
        $this->assertTrue($validator->errors()->has('evacuation_center_id'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $householdId = DB::connection('mysql_v2')->table('households')->value('household_id');
        if (!$householdId) {
            $householdId = 'HH-TEST-VERIFY';
            DB::connection('mysql_v2')->table('households')->insert([
                'household_id' => $householdId,
                'household_name' => 'Verify Test Household',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->value('evacuation_center_id');
        if (!$centerId) {
            $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->insertGetId([
                'name' => 'Verify Center',
                'latitude' => 10.123,
                'longitude' => 123.456,
                'capacity' => 100,
                'osm_address' => 'Test Address',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validator = Validator::make([
            'household_id'         => $householdId,
            'evacuation_center_id' => $centerId,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
