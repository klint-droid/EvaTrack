<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Room extends Model
{
    protected $table = 'rooms';
    protected $primaryKey = 'room_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'room_id',
        'evacuation_center_id',
        'room_number',
        'max_capacity',
        'current_occupancy'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->room_id) {
                $model->room_id = (string) Str::uuid();
            }
        });
    }

    public function assignments()
    {
        return $this->hasMany(RoomAssignment::class, 'room_id', 'room_id');
    }

    public function evacuationCenter()
    {
        return $this->belongsTo(
            EvacuationCenter::class,
            'evacuation_center_id',
            'evacuation_center_id'
        );
    }

    public function isFull()
    {
        return $this->current_occupancy >= $this->max_capacity;
    }

    public function availableSlots()
    {
        return $this->max_capacity - $this->current_occupancy;
    }

    public function getStatusAttribute()
    {
        if ($this->isFull()) return 'full';
        if ($this->current_occupancy >= $this->max_capacity * 0.8) return 'almost_full';
        return 'available';
    }
}