<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseholdMember extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'member_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'household_id',
        'name',
        'age',
        'gender',
        'is_pwd',
        'is_pregnant',
        'relation'
    ];

    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id');
    }
}