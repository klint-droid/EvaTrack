<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HouseholdMember extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'member_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'household_id',
        'first_name',
        'middle_name',
        'last_name',
        'birthdate',
        'gender_id',
        'civil_status_id',
        'relationship_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->member_id) {
                do {
                    $id = 'HM-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('member_id', $id)->exists());
                $model->member_id = $id;
            }
        });
    }

        public function household()
    {
        return $this->belongsTo(Household::class, 'household_id', 'household_id');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'gender_id');
    }

    public function relationshipDetail()
    {
        return $this->belongsTo(Relationship::class, 'relationship_id', 'relationship_id');
    }

    public function civilStatusDetail()
    {
        return $this->belongsTo(CivilStatus::class, 'civil_status_id', 'status_id');
    }

    public function vulnerableGroups()
    {
        return $this->hasMany(MemberVulnerableGroup::class, 'member_id', 'member_id');
    }

    public function evacuatedMembers()
    {
        return $this->hasMany(EvacuatedMember::class, 'member_id', 'member_id');
    }
}