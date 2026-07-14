<?php

namespace App\Domains\ReferenceData\Models;

use Illuminate\Database\Eloquent\Model;

class VulnerableGroup extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'vulnerable_groups';
    protected $primaryKey = 'vulnerable_group_id';
    public $timestamps = false;

    protected $fillable = ['vulnerable_group_key', 'vulnerable_group_label'];

    protected $appends = ['id', 'label'];

    public function getIdAttribute()
    {
        return $this->vulnerable_group_id ?? ($this->attributes['id'] ?? null);
    }

    public function getLabelAttribute()
    {
        return $this->vulnerable_group_label ?? ($this->attributes['label'] ?? null);
    }

    public function memberVulnerableGroups()
    {
        return $this->hasMany(MemberVulnerableGroup::class, 'vulnerable_group_id', 'vulnerable_group_id');
    }
}