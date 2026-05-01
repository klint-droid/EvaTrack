<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZipCode extends Model
{
    protected $table = 'zip_codes';
    protected $primaryKey = 'zipcode_id';
    public $timestamps = false;

    protected $fillable = [
        'zipcode_name',
        'purok_id',
        'sitio_id',
        'barangay_id',
        'city_id',
        'province_id',
        'region_id',
    ];

    public function purok()
    {
        return $this->belongsTo(Purok::class, 'purok_id', 'purok_id');
    }

    public function sitio()
    {
        return $this->belongsTo(Sitio::class, 'sitio_id', 'sitio_id');
    }

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
}