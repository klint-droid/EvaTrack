<?php

namespace App\Domains\Analytics\Models;

use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Analytic extends Model
{
    protected $connection = 'mysql_v2';

    protected $table = 'analytics';

    protected $primaryKey = 'analytic_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [

        'evacuation_event_id',
        'evacuation_center_id',

        'snapshot_type',
        'recorded_at',

        'total_population',
        'total_households',

        'children_count',
        'adult_count',
        'elderly_count',

        'pwd_count',
        'pregnant_count',

        'male_count',
        'female_count',
    ];

    protected $casts = [

        'recorded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            if (!$model->analytic_id) {

                do {

                    $id = 'AN-' . date('Y') . '-' . strtoupper(Str::random(6));

                } while (
                    self::where('analytic_id', $id)->exists()
                );

                $model->analytic_id = $id;
            }
        });
    }

    public function event()
    {
        return $this->belongsTo(
            DisasterEvent::class,
            'evacuation_event_id',
            'event_id'
        );
    }

    public function center()
    {
        return $this->belongsTo(
            EvacuationCenter::class,
            'evacuation_center_id',
            'evacuation_center_id'
        );
    }

    public function isOverallEventSnapshot()
    {
        return is_null($this->evacuation_center_id);
    }

    public function isCenterSnapshot()
    {
        return !is_null($this->evacuation_center_id);
    }
}