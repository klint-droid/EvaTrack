<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EvacuationCenter extends Model
{
    protected $connection = 'mysql_v2';

    protected $primaryKey = 'evacuation_center_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'address_id',
        'latitude',
        'longitude',
        'capacity',
        'current_event_id',
        'created_at',
        'deleted_at'
    ];
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($center) {
            if (!$center->evacuation_center_id) {

                do {
                    $id = 'EC-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('evacuation_center_id', $id)->exists());

                $center->evacuation_center_id = $id;
            }
        });
    }

    public function currentEvent()
    {
        return $this->belongsTo(DisasterEvent::class, 'current_event_id', 'event_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id', 'address_id');
    }

    public function accommodationUnits()
    {
        return $this->hasMany(AccommodationUnit::class, 'center_id', 'evacuation_center_id');
    }

    public function evacuationRecords()
    {
        return $this->hasMany(EvacuationRecord::class, 'center_id', 'evacuation_center_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    public function centerOccupancy()
    {
        return $this->hasOne(CenterOccupancy::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    public function issueReports()
    {
        return $this->hasMany(CenterIssueReport::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    public function resourceRequests()
    {
        return $this->hasMany(ResourceRequest::class, 'evacuation_center_id', 'evacuation_center_id');
    }
}