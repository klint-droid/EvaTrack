<?php

namespace App\Domains\Households\Models;

use App\Domains\ReferenceData\Models\Address;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Evacuations\Models\EvacuatedMember;
use App\Domains\ReferenceData\Models\Gender;
use App\Domains\ReferenceData\Models\Relationship;
use App\Domains\ReferenceData\Models\CivilStatus;
use App\Domains\ReferenceData\Models\MemberVulnerableGroup;
use App\Domains\EvacuationEvents\Models\DisasterEvent;

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