<?php

namespace App\Domains\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationStatus extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'notification_statuses';
    protected $primaryKey = 'status_id';
    public $timestamps = false;

    protected $fillable = ['status_key', 'status_label'];

    public function logs()
    {
        return $this->hasMany(NotificationLog::class, 'status_id', 'status_id');
    }
}