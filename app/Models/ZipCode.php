<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZipCode extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'zipcodes';
    protected $primaryKey = 'zipcode_id';
    public $timestamps = false;

    protected $fillable = ['zipcode', 'city_id'];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'zipcode_id', 'zipcode_id');
    }
}