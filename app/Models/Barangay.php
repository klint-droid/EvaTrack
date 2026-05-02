<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'barangays';
    protected $primaryKey = 'barangay_id';
    public $timestamps = false;

    protected $fillable = ['barangay_code', 'barangay_name', 'city_id'];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function sitios()
    {
        return $this->hasMany(Sitio::class, 'barangay_id', 'barangay_id');
    }

    public function puroks()
    {
        return $this->hasMany(Purok::class, 'barangay_id', 'barangay_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'barangay_id', 'barangay_id');
    }
}