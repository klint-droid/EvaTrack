<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseholdStatus extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Status ID Constants
    |--------------------------------------------------------------------------
    | These must match the seeded values in the household_statuses table.
    */
    const NOT_VERIFIED = 1;
    const EVACUATED    = 2;
    const CHECKED_OUT  = 6;

    protected $connection = 'mysql_v2';
    protected $table = 'household_statuses';
    protected $primaryKey = 'status_id';
    public $timestamps = false;

    protected $fillable = ['status_key', 'status_label'];

    public function evacuationRecords()
    {
        return $this->hasMany(EvacuationRecord::class, 'status_id', 'status_id');
    }
}