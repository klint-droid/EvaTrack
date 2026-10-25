<?php

namespace Tests\Unit\Requests\Notifications;

use App\Domains\Notifications\Requests\SendNotificationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SendNotificationRequestTest extends TestCase
{
    private function createRequest(array $data = []): SendNotificationRequest
    {
        $request = SendNotificationRequest::create('/api/notifications', 'POST', $data);
        $request->setContainer(app());
        return $request;
    }

    public function test_it_authorizes_the_request(): void
    {
        $request = $this->createRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $request = $this->createRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('message', $rules);
        $this->assertArrayHasKey('channel', $rules);
        $this->assertArrayHasKey('target_filter', $rules);
        $this->assertArrayHasKey('evacuation_event_id', $rules);
        $this->assertArrayHasKey('evacuation_center_id', $rules);
        $this->assertArrayHasKey('scheduled_at', $rules);
        $this->assertArrayHasKey('is_recurring', $rules);
        $this->assertArrayHasKey('recurrence_type', $rules);
        $this->assertArrayHasKey('recurrence_end_at', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $request = $this->createRequest([]);
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('message'));
        $this->assertTrue($validator->errors()->has('channel'));
        $this->assertTrue($validator->errors()->has('target_filter'));
    }

    public function test_validation_fails_when_channel_is_invalid(): void
    {
        $data = [
            'message'       => 'Flood alert',
            'channel'       => 'email_only',
            'target_filter' => 'all',
        ];
        $request = $this->createRequest($data);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('channel'));
    }

    public function test_validation_fails_when_target_filter_is_invalid(): void
    {
        $data = [
            'message'       => 'Flood alert',
            'channel'       => 'sms',
            'target_filter' => 'unknown_filter',
        ];
        $request = $this->createRequest($data);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('target_filter'));
    }

    public function test_validation_fails_when_message_exceeds_max_length(): void
    {
        $data = [
            'message'       => str_repeat('M', 1001),
            'channel'       => 'both',
            'target_filter' => 'all',
        ];
        $request = $this->createRequest($data);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('message'));
    }

    public function test_validation_fails_when_recurring_fields_are_missing_if_recurring_is_true(): void
    {
        $data = [
            'message'       => 'Daily advisory',
            'channel'       => 'push',
            'target_filter' => 'all',
            'is_recurring'  => true,
            // recurrence_type and recurrence_end_at missing
        ];
        $request = $this->createRequest($data);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('recurrence_type'));
        $this->assertTrue($validator->errors()->has('recurrence_end_at'));
    }

    public function test_validation_fails_when_recurrence_type_is_invalid(): void
    {
        $data = [
            'message'           => 'Daily advisory',
            'channel'           => 'push',
            'target_filter'     => 'all',
            'is_recurring'      => true,
            'recurrence_type'   => 'monthly', // only hourly, daily, weekly allowed
            'recurrence_end_at' => now()->addDays(5)->toDateTimeString(),
        ];
        $request = $this->createRequest($data);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('recurrence_type'));
    }

    public function test_validation_passes_for_valid_instant_notification(): void
    {
        $data = [
            'message'       => 'Heavy rainfall warning in effect. Please stay indoors.',
            'channel'       => 'both',
            'target_filter' => 'all',
            'is_recurring'  => false,
        ];
        $request = $this->createRequest($data);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_validation_passes_for_valid_recurring_notification(): void
    {
        $data = [
            'message'           => 'Daily headcount reminder for center personnel.',
            'channel'           => 'sms',
            'target_filter'     => 'evacuated',
            'is_recurring'      => true,
            'recurrence_type'   => 'daily',
            'recurrence_end_at' => now()->addDays(7)->toDateTimeString(),
        ];
        $request = $this->createRequest($data);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }
}
