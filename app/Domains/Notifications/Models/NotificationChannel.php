<?php

namespace App\Domains\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationChannel extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'notification_channels';
    protected $primaryKey = 'channel_id';
    public $timestamps = false;

    protected $fillable = ['channel_key', 'channel_label'];

    public function logs()
    {
        return $this->hasMany(NotificationLog::class, 'channel_id', 'channel_id');
    }
}