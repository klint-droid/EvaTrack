<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = 'provinces';
    protected $primaryKey = 'province_id';
    public $timestamps = false;

    protected $fillable = ['province_name', 'region_id'];

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'region_id');
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'province_id', 'province_id');
    }

    public function barangays()
    {
        return $this->hasMany(Barangay::class, 'province_id', 'province_id');
    }

    public function sitios()
    {
        return $this->hasMany(Sitio::class, 'province_id', 'province_id');
    }

    public function puroks()
    {
        return $this->hasMany(Purok::class, 'province_id', 'province_id');
    }

    public function zipCodes()
    {
        return $this->hasMany(ZipCode::class, 'province_id', 'province_id');
    }
}