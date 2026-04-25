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
        'status',
        'evacuated_count',
        'method',
        'verified_by',
        'verified_at'
    ];

    // 🏠 Household
    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id');
    }

    // 🏢 Center
    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'center_id');
    }

    // 👤 Verified by user
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    // 👥 Evacuated members (RENAMED for clarity)
    public function evacuatedMembers()
    {
        return $this->hasMany(EvacuatedMember::class, 'evacuation_id', 'evacuation_id');
    }

    // 🛏️ Room allocation
    public function unitAllocation()
    {
        return $this->hasOne(UnitAllocation::class, 'evacuation_id', 'evacuation_id');
    }

    public function event()
    {
        return $this->belongsTo(EvacuationEvent::class, 'event_id', 'event_id');
    }
}