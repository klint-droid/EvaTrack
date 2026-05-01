<?php

namespace App\Models;

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
        'evacuation_center_id',
        'purok_id',
        'sitio_id',
        'total_population',
        'total_household',
        'children_count',
        'adult_count',
        'elderly_count',
        'pwd_count',
        'pregnant_count',
        'male_count',
        'female_count',
        'recorded_at',
    ];

    protected $casts = ['recorded_at' => 'datetime'];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->analytic_id) {
                do {
                    $id = 'NHH-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('analytic_id', $id)->exists());

                $model->analytic_id = $id;
            }
        });
    }

    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    public function purok()
    {
        return $this->belongsTo(Purok::class, 'purok_id', 'purok_id');
    }

    public function sitio()
    {
        return $this->belongsTo(Sitio::class, 'sitio_id', 'sitio_id');
    }
}