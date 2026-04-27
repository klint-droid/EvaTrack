<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HouseholdMember extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'member_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'household_id',
        'name',
        'age',
        'gender',
        'is_pwd',
        'is_pregnant',
        'relation',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->member_id) {
                do {
                    $id = 'HM-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('member_id', $id)->exists());
                $model->member_id = $id;
            }
        });
    }

    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id');
    }

    public function evacuatedEntries(){
        return $this->hasMany(EvacuatedMember::class, 'member_id', 'member_id');
    }
}