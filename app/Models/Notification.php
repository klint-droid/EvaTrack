<?php

namespace App\Models;

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
        'urgency_level_id',
        'scheduled_at',
        'is_recurring',
        'recurrence_type_id',
        'recurrence_end_at',
        'last_sent_at',
        'channel',
        'status',
        'target_filter',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_recurring' => 'boolean',
        'recurrence_end_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $appends = ['recurrence_type'];

    public function getRecurrenceTypeAttribute()
    {
        return $this->relationLoaded('recurrenceType')
            ? $this->getRelationValue('recurrenceType')?->type_key
            : $this->recurrenceType()->first()?->type_key;
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

    public function urgencyLevel()
    {
        return $this->belongsTo(UrgencyLevel::class, 'urgency_level_id', 'urgency_id');
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