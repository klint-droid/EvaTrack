<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccommodationType extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'accommodation_types';
    protected $primaryKey = 'type_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'type_id',
        'type_key',
        'type_label',
    ];

    public function units()
    {
        return $this->hasMany(AccommodationUnit::class, 'type_id', 'type_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->type_id) {
                $model->type_id = 'AT-' . date('Y') . '-' . strtoupper(Str::random(6));
            }
        });
    }
}