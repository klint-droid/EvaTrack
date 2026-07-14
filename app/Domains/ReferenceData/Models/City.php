<?php

namespace App\Domains\ReferenceData\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'cities';
    protected $primaryKey = 'city_id';
    public $timestamps = false;

    protected $fillable = ['city_code', 'city_name', 'province_id'];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function barangays()
    {
        return $this->hasMany(Barangay::class, 'city_id', 'city_id');
    }

    public function zipcodes()
    {
        return $this->hasMany(ZipCode::class, 'city_id', 'city_id');
    }
}