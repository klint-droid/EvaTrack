<?php

namespace Tests\Unit\Requests\Evacuations;

use App\Domains\Evacuations\Requests\VerifyManualEvacuationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VerifyManualEvacuationRequestTest extends TestCase
{
    private VerifyManualEvacuationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new VerifyManualEvacuationRequest();
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
        $this->assertArrayHasKey('event_id', $rules);
        $this->assertArrayHasKey('member_count', $rules);
        $this->assertArrayHasKey('member_ids', $rules);
    }

    public function test_validation_fails_when_household_id_is_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_id'));
    }

    public function test_validation_fails_when_household_does_not_exist(): void
    {
        $validator = Validator::make([
            'household_id' => 'NON-EXISTENT-HOUSEHOLD',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_id'));
    }

    public function test_validation_fails_when_event_does_not_exist(): void
    {
        $householdId = DB::connection('mysql_v2')->table('households')->value('household_id');

        $validator = Validator::make([
            'household_id' => $householdId ?? 'HH-001',
            'event_id'     => 'NON-EXISTENT-EVENT',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('event_id'));
    }

    public function test_validation_fails_when_member_count_is_less_than_one(): void
    {
        $householdId = DB::connection('mysql_v2')->table('households')->value('household_id');

        $validator = Validator::make([
            'household_id' => $householdId ?? 'HH-001',
            'member_count' => 0,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('member_count'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $householdId = DB::connection('mysql_v2')->table('households')->value('household_id');
        if (!$householdId) {
            $householdId = 'HH-TEST-MANUAL';
            DB::connection('mysql_v2')->table('households')->insert([
                'household_id' => $householdId,
                'household_name' => 'Manual Evacuation Household',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validator = Validator::make([
            'household_id' => $householdId,
            'event_id'     => null,
            'member_count' => 4,
            'member_ids'   => [],
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
