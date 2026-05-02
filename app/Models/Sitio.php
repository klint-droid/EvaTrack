<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sitio extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'sitios';
    protected $primaryKey = 'sitio_id';
    public $timestamps = false;

    protected $fillable = ['sitio_name', 'barangay_id'];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function puroks()
    {
        return $this->hasMany(Purok::class, 'sitio_id', 'sitio_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'sitio_id', 'sitio_id');
    }
}