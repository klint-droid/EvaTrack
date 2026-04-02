<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomAssignment extends Model
{
    //
    protected $table = 'room_assignments';
    protected $primaryKey = 'room_assignment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'room_id',
        'evacuation_id',
        'household_id',
        'assigned_by',
        'is_self_selected',
    ];

    public function room(){
        return $this->belongsTo(Room::class);
    }
}
