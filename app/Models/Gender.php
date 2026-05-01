<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'genders';
    protected $primaryKey = 'gender_id';
    public $timestamps = false;

    protected $fillable = ['gender_key', 'gender_label'];

    public function members()
    {
        return $this->hasMany(HouseholdMember::class, 'gender_id', 'gender_id');
    }
}