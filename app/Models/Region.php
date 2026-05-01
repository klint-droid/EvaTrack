<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'regions';
    protected $primaryKey = 'region_id';
    public $timestamps = false;

    protected $fillable = ['region_name'];

    public function provinces()
    {
        return $this->hasMany(Province::class, 'region_id', 'region_id');
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'region_id', 'region_id');
    }

    public function barangays()
    {
        return $this->hasMany(Barangay::class, 'region_id', 'region_id');
    }

    public function sitios()
    {
        return $this->hasMany(Sitio::class, 'region_id', 'region_id');
    }

    public function puroks()
    {
        return $this->hasMany(Purok::class, 'region_id', 'region_id');
    }

    public function zipCodes()
    {
        return $this->hasMany(ZipCode::class, 'region_id', 'region_id');
    }
}