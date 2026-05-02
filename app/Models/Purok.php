<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purok extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'puroks';
    protected $primaryKey = 'purok_id';
    public $timestamps = false;

    protected $fillable = ['purok_name', 'sitio_id'];

    public function sitio()
    {
        return $this->belongsTo(Sitio::class, 'sitio_id', 'sitio_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'purok_id', 'purok_id');
    }
}