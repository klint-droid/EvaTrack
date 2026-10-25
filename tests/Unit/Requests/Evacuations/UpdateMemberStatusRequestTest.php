<?php

namespace Tests\Unit\Requests\Evacuations;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateMemberStatusRequestTest extends TestCase
{
    private array $rules = [
        'status' => 'required|string|in:Inside Center,Checked Out,Transferred,evacuated,not_verified',
    ];

    public function test_validation_fails_when_status_is_missing(): void
    {
        $validator = Validator::make([], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_fails_when_status_is_invalid(): void
    {
        $validator = Validator::make([
            'status' => 'UnknownStatus',
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_passes_for_all_valid_member_statuses(): void
    {
        $validStatuses = [
            'Inside Center',
            'Checked Out',
            'Transferred',
            'evacuated',
            'not_verified',
        ];

        foreach ($validStatuses as $status) {
            $validator = Validator::make([
                'status' => $status,
            ], $this->rules);

            $this->assertFalse($validator->fails(), "Failed asserting status '{$status}' is valid.");
        }
    }
}
