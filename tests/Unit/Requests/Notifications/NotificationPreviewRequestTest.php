<?php

namespace Tests\Unit\Requests\Notifications;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NotificationPreviewRequestTest extends TestCase
{
    private array $rules = [
        'target_filter'        => 'required|in:all,evacuated,not_evacuated',
        'evacuation_center_id' => 'nullable|exists:evacuation_centers,evacuation_center_id',
        'evacuation_event_id'  => 'nullable|exists:evacuation_events,event_id',
    ];

    public function test_validation_fails_when_target_filter_is_missing(): void
    {
        $validator = Validator::make([], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('target_filter'));
    }

    public function test_validation_fails_when_target_filter_is_invalid(): void
    {
        $validator = Validator::make([
            'target_filter' => 'invalid_scope',
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('target_filter'));
    }

    public function test_validation_fails_when_evacuation_center_id_does_not_exist(): void
    {
        $validator = Validator::make([
            'target_filter'        => 'all',
            'evacuation_center_id' => 99999999,
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('evacuation_center_id'));
    }

    public function test_validation_passes_with_valid_filter_and_optional_ids(): void
    {
        $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->value('evacuation_center_id');

        $validator = Validator::make([
            'target_filter'        => 'evacuated',
            'evacuation_center_id' => $centerId,
            'evacuation_event_id'  => null,
        ], $this->rules);

        $this->assertFalse($validator->fails());
    }
}
