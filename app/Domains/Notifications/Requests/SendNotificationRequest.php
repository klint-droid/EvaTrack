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
            'urgency_level_id'     => 'required|exists:urgency_levels,urgency_id',
            'channel'              => 'required|in:sms,push,both',
            'target_filter'        => 'required|in:all,evacuated,not_evacuated',
            'evacuation_event_id'  => 'nullable|exists:evacuation_events,event_id',
            'evacuation_center_id' => 'nullable|exists:evacuation_centers,evacuation_center_id',
            'scheduled_at'         => 'nullable|date|after:now',
            'is_recurring'         => 'boolean',
            'recurrence_type'      => 'required_if:is_recurring,true|nullable|in:hourly,daily,weekly',
            'recurrence_end_at'    => 'required_if:is_recurring,true|nullable|date|after:' . ($this->input('scheduled_at') ?: 'now'),
        ];
    }
}