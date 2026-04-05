<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RoomAssignment extends Model
{
    protected $table = 'room_assignments';
    protected $primaryKey = 'room_assignment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'room_id',
        'evacuation_id',
        'household_id',
        'assigned_by',
        'is_self_selected',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->room_assignment_id) {
                $model->room_assignment_id = (string) Str::uuid();
            }
        });
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function evacuation()
    {
        return $this->belongsTo(
            Evacuation::class,
            'evacuation_id',
            'evacuation_id'
        );
    }

    public function household()
    {
        return $this->belongsTo(
            Household::class,
            'household_id',
            'household_id'
        );
    }

    public function assignedBy()
    {
        return $this->belongsTo(
            User::class,
            'assigned_by',
            'user_id'
        );
    }

    // Helper
    public static function alreadyAssigned($householdId, $evacuationId)
    {
        return self::where('household_id', $householdId)
            ->where('evacuation_id', $evacuationId)
            ->exists();
    }
}