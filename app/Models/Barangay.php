<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    protected $table = 'barangays';
    protected $primaryKey = 'barangay_id';
    public $timestamps = false;

    protected $fillable = ['barangay_name', 'city_id', 'province_id', 'region_id'];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'region_id');
    }

    public function sitios()
    {
        return $this->hasMany(Sitio::class, 'barangay_id', 'barangay_id');
    }

    public function puroks()
    {
        return $this->hasMany(Purok::class, 'barangay_id', 'barangay_id');
    }
}