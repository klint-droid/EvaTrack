<?php

namespace App\Domains\ReferenceData\Models;

use App\Domains\Households\Models\HouseholdMember;

use Illuminate\Database\Eloquent\Model;

class MemberVulnerableGroup extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'member_vulnerable_groups';
    public $timestamps = false;

    protected $fillable = ['member_id', 'vulnerable_group_id'];

    public function member()
    {
        return $this->belongsTo(HouseholdMember::class, 'member_id', 'member_id');
    }

    public function vulnerableGroup()
    {
        return $this->belongsTo(VulnerableGroup::class, 'vulnerable_group_id', 'vulnerable_group_id');
    }
}