<?php

namespace App\Domains\ReferenceData\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'regions';
    protected $primaryKey = 'region_id';
    public $timestamps = false;

    protected $fillable = ['region_code', 'region_name'];

    public function provinces()
    {
        return $this->hasMany(Province::class, 'region_id', 'region_id');
    }

    public function cities()
    {
        return $this->hasManyThrough(City::class, Province::class, 'region_id', 'province_id');
    }

    public function barangays()
    {
        return $this->hasManyThrough(Barangay::class, City::class, 'province_id', 'city_id');
    }
}