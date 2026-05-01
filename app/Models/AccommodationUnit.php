<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccommodationUnit extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql_v2';
    protected $table = 'accommodation_units';
    protected $primaryKey = 'unit_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'center_id',
        'name',
        'type_id',
        'max_capacity',
        'current_occupancy',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'center_id', 'evacuation_center_id');
    }

    public function type()
    {
        return $this->belongsTo(AccommodationType::class, 'type_id', 'type_id');
    }

    public function unitAllocations()
    {
        return $this->hasMany(UnitAllocation::class, 'unit_id', 'unit_id');
    }
}