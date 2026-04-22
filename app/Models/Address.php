<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Address extends Model
{
    protected $connection = 'mysql_v2';

    protected $primaryKey = 'address_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'region',
        'province',
        'city',
        'barangay',
        'street',
        'purok',
        'full_address'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->address_id) {
                do {
                    $id = 'ADDR-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('address_id', $id)->exists());

                $model->address_id = $id;
            }
        });
    }

    public function households()
    {
        return $this->hasMany(Household::class, 'address_id', 'address_id');
    }

    public function evacuationCenters()
    {
        return $this->hasMany(EvacuationCenter::class, 'address_id', 'address_id');
    }
}