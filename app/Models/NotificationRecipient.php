<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRecipient extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'notification_recipients';

    protected $fillable = [
        'notification_id',
        'household_id',
        'read_at',
        'acknowledged_at'
    ];

    public $timestamps = false;

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id', 'notif_id');
    }

    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id', 'household_id');
    }
}