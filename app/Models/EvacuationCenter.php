<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EvacuationRecord;

class EvacuationCenter extends Model
{
    protected $table = 'evacuation_centers';
    protected $primaryKey = 'evacuation_center_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'evacuation_center_id',
        'name',
        'location',
        'capacity',
    ];

    public function evacuationRecords()
    {
        return $this->hasMany(EvacuationRecord::class, 'evacuation_center_id', 'evacuation_center_id');
    }
}