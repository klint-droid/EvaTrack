<?php

namespace Tests\Unit\Requests\Notifications;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NotificationAcknowledgeRequestTest extends TestCase
{
    private array $rules = [
        'household_id' => 'required|exists:households,household_id',
    ];

    public function test_validation_fails_when_household_id_is_missing(): void
    {
        $validator = Validator::make([], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_id'));
    }

    public function test_validation_fails_when_household_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'household_id' => 'NON-EXISTENT-HOUSEHOLD-ID',
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('household_id'));
    }

    public function test_validation_passes_when_household_id_exists(): void
    {
        $householdId = DB::connection('mysql_v2')->table('households')->value('household_id');
        if (!$householdId) {
            $householdId = 'HH-TEST-ACK';
            DB::connection('mysql_v2')->table('households')->insert([
                'household_id'   => $householdId,
                'household_name' => 'Acknowledge Test Household',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $validator = Validator::make([
            'household_id' => $householdId,
        ], $this->rules);

        $this->assertFalse($validator->fails());
    }
}
