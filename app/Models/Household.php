<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Household extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'household_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->household_id) {
                do {
                    $id = 'NHH-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('household_id', $id)->exists());

                $model->household_id = $id;
            }
        });
    }

    protected $fillable = [
        'household_name',
        'member_count',
        'address_id',
        'contact_number',
        'created_at',
        'deleted_at'
    ];

    public function members()
    {
        return $this->hasMany(HouseholdMember::class, 'household_id');
    }

    public function evacuations()
    {
        return $this->hasMany(EvacuationRecord::class, 'household_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id', 'address_id');
    }
}