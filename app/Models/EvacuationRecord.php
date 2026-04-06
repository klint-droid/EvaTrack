<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvacuationRecord extends Model
{
    protected $primaryKey = 'evacuation_id';
    public $incrementing = false;

    protected $fillable = [
        'evacuation_id',
        'household_id',
        'evacuation_center_id',
        'room_assignment_id',
        'status',
        'verified_by',
        'method',
        'verified_at'
    ];

    public function evacuationCenter()
    {
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id', 'household_id');
    }
}