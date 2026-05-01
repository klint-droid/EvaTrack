<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitAllocation extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'unit_allocations';
    protected $primaryKey = 'allocation_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'evacuation_id',
        'unit_id',
        'assigned_by',
        'selected_by_resident',
    ];

    protected $casts = [
        'selected_by_resident' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function evacuationRecord()
    {
        return $this->belongsTo(EvacuationRecord::class, 'evacuation_id', 'evacuation_id');
    }

    public function unit()
    {
        return $this->belongsTo(AccommodationUnit::class, 'unit_id', 'unit_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_id');
    }
}