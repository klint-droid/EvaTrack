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
        'created_at',
        'deleted_at'
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

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id', 'address_id');
    }

    public function accommodationUnits()
    {
        return $this->hasMany(AccommodationUnit::class, 'center_id', 'evacuation_center_id');
    }

    public function evacuations()
    {
        return $this->hasMany(EvacuationRecord::class, 'center_id', 'evacuation_center_id');
    }
}