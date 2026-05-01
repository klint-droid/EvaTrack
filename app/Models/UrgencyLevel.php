<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UrgencyLevel extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'urgency_levels';
    protected $primaryKey = 'urgency_id';
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'urgency_id',
        'urgency_key',
        'urgency_label',
    ];

    public function resourceRequests()
    {
        return $this->hasMany(ResourceRequest::class, 'urgency_id', 'urgency_id');
    }
}