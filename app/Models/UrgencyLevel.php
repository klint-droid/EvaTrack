<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrgencyLevel extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'urgency_levels';
    protected $primaryKey = 'urgency_id';
    public $timestamps = false;

    protected $fillable = ['urgency_key', 'urgency_label'];

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'urgency_level_id', 'urgency_id');
    }

    public function resourceRequests()
    {
        return $this->hasMany(ResourceRequest::class, 'urgency_id', 'urgency_id');
    }
}