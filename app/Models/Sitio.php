<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sitio extends Model
{
    protected $table = 'sitios';
    protected $primaryKey = 'sitio_id';
    public $timestamps = false;

    protected $fillable = ['sitio_name', 'barangay_id', 'city_id', 'province_id', 'region_id'];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

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

    public function puroks()
    {
        return $this->hasMany(Purok::class, 'sitio_id', 'sitio_id');
    }
}