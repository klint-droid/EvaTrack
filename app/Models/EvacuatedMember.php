<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvacuatedMember extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'evacuated_members';
    protected $primaryKey = 'evacuated_member_id';
    public $timestamps = false;

    protected $fillable = ['evacuation_id', 'member_id', 'verified_at'];

    protected $casts = ['verified_at' => 'datetime'];

    public function evacuationRecord()
    {
        return $this->belongsTo(EvacuationRecord::class, 'evacuation_id', 'evacuation_id');
    }

    public function member()
    {
        return $this->belongsTo(HouseholdMember::class, 'member_id', 'member_id');
    }
}