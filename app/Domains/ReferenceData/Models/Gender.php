<?php

namespace App\Domains\ReferenceData\Models;

use App\Domains\Households\Models\HouseholdMember;

use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'genders';
    protected $primaryKey = 'gender_id';
    public $timestamps = false;

    protected $fillable = ['gender_key', 'gender_label'];

    protected $appends = ['label'];

    public function getLabelAttribute()
    {
        return $this->gender_label ?? ($this->attributes['label'] ?? null);
    }

    public function members()
    {
        return $this->hasMany(HouseholdMember::class, 'gender_id', 'gender_id');
    }
}