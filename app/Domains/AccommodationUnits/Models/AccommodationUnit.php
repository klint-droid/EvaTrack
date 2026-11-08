<?php

namespace App\Domains\AccommodationUnits\Models;

use App\Domains\EvacuationCenters\Models\EvacuationCenter;

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
    ];

    protected $appends = ['current_occupancy'];

    public function getCurrentOccupancyAttribute()
    {
        if (array_key_exists('current_occupancy', $this->attributes)) {
            return (int) $this->attributes['current_occupancy'];
        }

        return (int) $this->unitAllocations()
            ->join('evacuation_records', 'unit_allocations.evacuation_id', '=', 'evacuation_records.evacuation_id')
            ->leftJoin('disaster_events', 'evacuation_records.event_id', '=', 'disaster_events.event_id')
            ->where('evacuation_records.household_status_id', \App\Domains\Households\Models\HouseholdStatus::EVACUATED)
            ->where(function ($q) {
                $q->whereNull('evacuation_records.event_id')
                  ->orWhere(function ($eq) {
                      $eq->whereNull('disaster_events.ended_at')
                         ->whereNull('disaster_events.deleted_at');
                  });
            })
            ->sum('evacuation_records.evacuated_count');
    }

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