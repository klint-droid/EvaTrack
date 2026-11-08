<?php

namespace App\Domains\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message'              => 'required|string|max:1000',
            'channel'              => 'required|in:sms,push,both',
            'target_filter'        => 'required|in:all,evacuated,not_evacuated',
            'evacuation_event_id'  => 'nullable|exists:evacuation_events,event_id',
            'evacuation_center_id' => 'nullable|exists:evacuation_centers,evacuation_center_id',
            'scheduled_at'         => 'nullable|date|after:now',
            'is_recurring'                => 'boolean',
            'recurrence_type'             => 'required_if:is_recurring,true|nullable|in:hourly,daily,weekly,custom',
            'recurrence_interval_value'   => 'required_if:recurrence_type,custom|nullable|integer|min:1|max:10000',
            'recurrence_interval_unit'    => 'required_if:recurrence_type,custom|nullable|in:minutes,hours,days,weeks',
            'recurrence_interval_minutes' => 'nullable|integer|min:1',
            'recurrence_end_at'           => 'required_if:is_recurring,true|nullable|date|after:' . ($this->input('scheduled_at') ?: 'now'),
        ];
    }
}