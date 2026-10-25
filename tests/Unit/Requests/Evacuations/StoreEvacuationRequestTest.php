<?php

namespace Tests\Unit\Requests\Evacuations;

use App\Domains\Evacuations\Requests\StoreEvacuationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreEvacuationRequestTest extends TestCase
{
    private StoreEvacuationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreEvacuationRequest();
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
        $this->assertArrayHasKey('member_ids', $rules);
        $this->assertArrayHasKey('member_ids.*', $rules);
    }

    public function test_validation_fails_when_household_id_is_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_id'));
    }

    public function test_validation_fails_when_household_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'household_id' => 'NON-EXISTENT-HH-999',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_id'));
    }

    public function test_validation_fails_when_member_ids_is_not_an_array(): void
    {
        $householdId = DB::connection('mysql_v2')->table('households')->value('household_id');

        $validator = Validator::make([
            'household_id' => $householdId ?? 'HH-001',
            'member_ids'   => 'single-member-string',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('member_ids'));
    }

    public function test_validation_passes_when_household_exists(): void
    {
        $householdId = DB::connection('mysql_v2')->table('households')->value('household_id');
        if (!$householdId) {
            $householdId = 'HH-TEST-STORE';
            DB::connection('mysql_v2')->table('households')->insert([
                'household_id' => $householdId,
                'household_name' => 'Test Household',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validator = Validator::make([
            'household_id' => $householdId,
            'member_ids'   => [],
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
