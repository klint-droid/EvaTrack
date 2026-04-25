<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccommodationUnit extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'accommodation_units';
    protected $primaryKey = 'unit_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'unit_id',
        'center_id',
        'name',
        'type_id',
        'max_capacity',
        'current_occupancy',
        'created_at',
        'deleted_at',
    ];

    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'center_id', 'evacuation_center_id');
    }

    public function type()
    {
        return $this->belongsTo(AccommodationType::class, 'type_id', 'type_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($unit) {
            if (!$unit->unit_id) {
                do {
                    $id = 'AU-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('unit_id', $id)->exists());
                $unit->unit_id = $id;
            }
        });
    }

    public function allocations()
{
    return $this->hasMany(UnitAllocation::class, 'unit_id', 'unit_id');
}
}