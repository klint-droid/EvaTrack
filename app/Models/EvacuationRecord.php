<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EvacuationRecord extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'evacuation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($evacuation) {

            $prefix = 'EV';
            $year = date('Y');

            do {
                $random = strtoupper(Str::random(6));
                $id = "{$prefix}-{$year}-{$random}";
            } while (self::where('evacuation_id', $id)->exists());

            $evacuation->evacuation_id = $id;
        });
    }

    protected $fillable = [
        'event_id',
        'household_id',
        'center_id',
        'household_status_id',
        'evacuated_count',
        'method',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(DisasterEvent::class, 'event_id', 'event_id');
    }

    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id', 'household_id');
    }

    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'center_id', 'evacuation_center_id');
    }

    public function status()
    {
        return $this->belongsTo(HouseholdStatus::class, 'household_status_id', 'household_status_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    public function evacuatedMembers()
    {
        return $this->hasMany(EvacuatedMember::class, 'evacuation_id', 'evacuation_id');
    }

    public function unitAllocations()
    {
        return $this->hasMany(UnitAllocation::class, 'evacuation_id', 'evacuation_id');
    }
}