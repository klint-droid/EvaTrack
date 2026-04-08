<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    //
    protected $primaryKey = 'household_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'household_id',
        'household_name',
        'phone_number',
        'qr_code'
    ];

    public function evacuationCenter(){
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    public function evacuationRecord(){
        return $this->hasOne(EvacuationRecord::class, 'household_id', 'household_id');
    }
}
