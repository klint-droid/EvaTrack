<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'token_id';
    protected $table = 'device_tokens';

    protected $fillable = [
        'household_id',
        'player_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id', 'household_id');
    }
}