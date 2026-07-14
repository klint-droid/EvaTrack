<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Households\Models\Household;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'notification_logs';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = [
        'notification_id',
        'household_id',
        'channel_id',
        'status_id',
        'sent_at',
        'retry_count',
        'external_message_id',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    protected $appends = ['channel', 'status'];

    public function getChannelAttribute()
    {
        $relation = $this->relationLoaded('channel')
            ? $this->getRelation('channel')
            : $this->channel()->first();

        return $relation?->channel_key;
    }

    public function getStatusAttribute()
    {
        $relation = $this->relationLoaded('status')
            ? $this->getRelation('status')
            : $this->status()->first();

        return $relation?->status_key;
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['channel'] = $this->getChannelAttribute();
        $array['status'] = $this->getStatusAttribute();
        return $array;
    }

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id', 'notif_id');
    }

    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id', 'household_id');
    }

    public function channel()
    {
        return $this->belongsTo(NotificationChannel::class, 'channel_id', 'channel_id');
    }

    public function status()
    {
        return $this->belongsTo(NotificationStatus::class, 'status_id', 'status_id');
    }
}