<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Authentication\Models\User;
use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'notifications';
    protected $primaryKey = 'notif_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'message',
        'sent_by',
        'evacuation_event_id',
        'evacuation_center_id',
        'scheduled_at',
        'is_recurring',
        'recurrence_type_id',
        'recurrence_end_at',
        'last_sent_at',
        'channel',
        'status',
        'target_filter',
        'feed_payload_json',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_recurring' => 'boolean',
        'recurrence_end_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'feed_payload_json' => 'array',
    ];

    protected $appends = [
        'recurrence_type',
        'recurrence_interval_minutes',
        'recurrence_interval_value',
        'recurrence_interval_unit',
    ];

    public function getRecurrenceTypeAttribute()
    {
        $feed = $this->feed_payload_json;
        if (is_string($feed)) {
            $feed = json_decode($feed, true);
        }
        if (!empty($feed['recurrence_type'])) {
            return $feed['recurrence_type'];
        }

        return $this->relationLoaded('recurrenceType')
            ? $this->getRelationValue('recurrenceType')?->type_key
            : $this->recurrenceType()->first()?->type_key;
    }

    public function getRecurrenceIntervalMinutesAttribute()
    {
        if (array_key_exists('recurrence_interval_minutes', $this->attributes) && $this->attributes['recurrence_interval_minutes'] !== null) {
            return (int) $this->attributes['recurrence_interval_minutes'];
        }
        $feed = $this->feed_payload_json;
        if (is_string($feed)) {
            $feed = json_decode($feed, true);
        }
        return isset($feed['recurrence_interval_minutes']) ? (int) $feed['recurrence_interval_minutes'] : null;
    }

    public function getRecurrenceIntervalValueAttribute()
    {
        $feed = $this->feed_payload_json;
        if (is_string($feed)) {
            $feed = json_decode($feed, true);
        }
        return $feed['recurrence_interval_value'] ?? null;
    }

    public function getRecurrenceIntervalUnitAttribute()
    {
        $feed = $this->feed_payload_json;
        if (is_string($feed)) {
            $feed = json_decode($feed, true);
        }
        return $feed['recurrence_interval_unit'] ?? null;
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by', 'user_id');
    }

    public function event()
    {
        return $this->belongsTo(DisasterEvent::class, 'evacuation_event_id', 'event_id');
    }

    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    public function recurrenceType()
    {
        return $this->belongsTo(RecurrenceType::class, 'recurrence_type_id', 'type_id');
    }

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id', 'notif_id');
    }

    public function logs()
    {
        return $this->hasMany(NotificationLog::class, 'notification_id', 'notif_id');
    }
}