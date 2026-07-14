<?php

namespace App\Domains\ReferenceData\Models;

use App\Domains\Households\Models\HouseholdMember;

use Illuminate\Database\Eloquent\Model;

class CivilStatus extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'civil_statuses';
    protected $primaryKey = 'status_id';
    public $timestamps = false;

    protected $fillable = [
        'status_key',
        'status_label'
    ];

    protected $appends = ['label'];

    public function getLabelAttribute()
    {
        return $this->status_label ?? ($this->attributes['label'] ?? null);
    }

    public function householdMembers(){
        return $this->hasMany(HouseholdMember::class, 'civil_status_id', 'status_id');
    }

}
