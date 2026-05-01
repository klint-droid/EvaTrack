<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'cities';
    protected $primaryKey = 'city_id';
    public $timestamps = false;

    protected $fillable = ['city_name', 'province_id', 'region_id'];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'region_id');
    }

    public function barangays()
    {
        return $this->hasMany(Barangay::class, 'city_id', 'city_id');
    }

    public function sitios()
    {
        return $this->hasMany(Sitio::class, 'city_id', 'city_id');
    }

    public function puroks()
    {
        return $this->hasMany(Purok::class, 'city_id', 'city_id');
    }
}