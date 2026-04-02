<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    //
    protected $table = 'rooms';
    protected $primaryKey = 'room_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'evacuation_center_id',
        'room_number',
        'max_capacity'
    ];

    public function assignments(){
        return $this->hasMany(RoomAssignment::class);
    }

    public function evacuationCenter(){
        return $this->belongsTo(EvacuationCenter::class);
    }
}
