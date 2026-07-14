<?php

namespace App\Domains\AccommodationUnits\Models;

use Illuminate\Database\Eloquent\Model;

class AccommodationType extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'accommodation_types';
    protected $primaryKey = 'type_id';
    public $timestamps = false;

    protected $fillable = ['type_key', 'type_label'];

    public function units()
    {
        return $this->hasMany(AccommodationUnit::class, 'type_id', 'type_id');
    }
}