<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvacuationCenter extends Model
{
    //
    protected $table = 'evacuation_centers';
    protected $primaryKey = 'evacuation_center_id';
    public $incrementing = false;

    protected $fillable = [
        'evacuation_center_id',
        'name',
        'location',
        'capacity',
        'current_occupancy'
    ];

    public function households(){
        return $this->hasMany(Household::class, 'evacuation_center_id', 'evacuation_center_id');
    }
}
