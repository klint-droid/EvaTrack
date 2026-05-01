<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VulnerableGroup extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'vulnerable_groups';
    protected $primaryKey = 'vulnerable_group_id';
    public $timestamps = false;

    protected $fillable = ['vulnerable_group_key', 'vulnerable_group_label'];

    public function memberVulnerableGroups()
    {
        return $this->hasMany(MemberVulnerableGroup::class, 'vulnerable_group_id', 'vulnerable_group_id');
    }
}