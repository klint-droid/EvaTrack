<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvacuationEvent extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'evacuation_events';
    protected $primaryKey = 'event_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'name',
        'type',
        'started_at',
        'ended_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->event_id) {
                do {
                    $id = 'EVT-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('event_id', $id)->exists());

                $model->event_id = $id;
            }
        });
    }

    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }

    public function evacuationRecords()
    {
        return $this->hasMany(EvacuationRecord::class, 'event_id', 'event_id');
    }
}