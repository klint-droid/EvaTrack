<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'notif_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->notif_id) {
                $model->notif_id =
                    'NOTIF-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
            }
        });
    }

    protected $fillable = [
        'message',
        'sent_by',
        'evacuation_event_id',
        'evacuation_center_id',
        'urgency_level_id',
        'scheduled_at',
        'created_at',
        'channel',
        'status',
        'target_filter',
        'is_recurring',        
        'recurrence_type',     
        'recurrence_end_at',  
        'last_sent_at',     
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by', 'user_id');
    }

    public function urgencyLevel()
    {
        return $this->belongsTo(UrgencyLevel::class, 'urgency_level_id', 'urgency_id');
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