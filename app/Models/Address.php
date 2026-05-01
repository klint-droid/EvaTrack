<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql_v2';
    protected $table = 'addresses';
    protected $primaryKey = 'address_id';
    protected $keyType = 'string';

    protected $fillable = [
        'street_address',
        'purok_id',
        'barangay_id',
        'sitio_id',
        'city_id',
        'province_id',
        'region_id',
        'zip_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function purok()
    {
        return $this->belongsTo(Purok::class, 'purok_id', 'purok_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function sitio()
    {
        return $this->belongsTo(Sitio::class, 'sitio_id', 'sitio_id');
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

    public function zipCode()
    {
        return $this->belongsTo(ZipCode::class, 'zip_code', 'zipcode_id');
    }

    public function households()
    {
        return $this->hasMany(Household::class, 'address_id', 'address_id');
    }

    public function evacuationCenters()
    {
        return $this->hasMany(EvacuationCenter::class, 'address_id', 'address_id');
    }
}