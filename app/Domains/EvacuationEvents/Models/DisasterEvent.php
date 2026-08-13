<?php

namespace App\Domains\EvacuationEvents\Models;

use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\AccommodationUnits\Models\UnitAllocation;
use App\Domains\Notifications\Models\Notification;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DisasterEvent extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql_v2';
    protected $table = 'disaster_events';
    protected $primaryKey = 'event_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'type_id',
        'severity_level_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->event_id) {
                do {
                    $id = 'EVT-' . date('Ymd') . '-' . strtoupper(Str::random(6));
                } while (self::where('event_id', $id)->exists());
                $model->event_id = $id;
            }
        });

        static::deleting(function ($event) {
            EvacuationCenter::where('current_event_id', $event->event_id)->update(['current_event_id' => null]);
            
            $evacIds = EvacuationRecord::where('event_id', $event->event_id)->pluck('evacuation_id')->toArray();
            if (!empty($evacIds)) {
                EvacuationRecord::whereIn('evacuation_id', $evacIds)->update([
                    'household_status_id' => 6,
                    'updated_at' => now(),
                ]);
                UnitAllocation::whereIn('evacuation_id', $evacIds)->delete();
            }
        });
    }

    public function severity()
    {
        return $this->belongsTo(SeverityLevel::class, 'severity_level_id', 'severity_id');
    }

    public function primaryType()
    {
        return $this->belongsTo(DisasterType::class, 'type_id', 'type_id');
    }

    public function types()
    {
        return $this->belongsToMany(
            DisasterType::class,
            'disaster_event_types',
            'event_id',
            'type_id'
        );
    }

    public function evacuationCenters()
    {
        return $this->hasMany(EvacuationCenter::class, 'current_event_id', 'event_id');
    }

    public function historicalCenters()
    {
        return $this->belongsToMany(
            EvacuationCenter::class,
            'event_center_history',
            'event_id',
            'evacuation_center_id'
        )->withTimestamps();
    }

    public function evacuationRecords()
    {
        return $this->hasMany(EvacuationRecord::class, 'event_id', 'event_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'evacuation_event_id', 'event_id');
    }
}