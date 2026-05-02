<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'provinces';
    protected $primaryKey = 'province_id';
    public $timestamps = false;

    protected $fillable = ['province_code', 'province_name', 'region_id'];

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
        return $this->hasManyThrough(Barangay::class, City::class, 'province_id', 'city_id');
    }
}