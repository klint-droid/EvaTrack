<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'log_id';

    
    protected $fillable = [
        'notification_id',
        'household_id',
        'channel',
        'status',
        'sent_at',
        'retry_count',
        'external_message_id'
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id', 'notif_id');
    }

    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id', 'household_id');
    }
}