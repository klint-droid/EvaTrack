<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $connection = 'mysql_v2';

    protected $fillable = [
        'household_id',
        'player_id'
    ];

    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id', 'household_id');
    }
}
