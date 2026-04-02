<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evacuation extends Model
{
    //
    protected $table = 'evacuation_records';
    protected $primaryKey = 'evacuation_id';

    protected $fillable = [
        'evacuation_id',
        'household_id',
        'evacuation_center_id',
        'status',
        'evacuated_at',
        'processed_by',
    ];

    public function evacuationCenter(){
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id', 'evacuation_center_id');
    }
}
