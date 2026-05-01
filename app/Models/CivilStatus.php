<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CivilStatus extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'civil_statuses';
    protected $primaryKey = 'status_id';
    public $timestamps = false;

    protected $fillable = ['status_key', 'status_label'];

    public function members()
    {
        return $this->hasMany(HouseholdMember::class, 'civil_status_id', 'status_id');
    }
}