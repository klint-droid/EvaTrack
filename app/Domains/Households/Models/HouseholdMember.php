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
use App\Domains\ReferenceData\Models\VulnerableGroup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class HouseholdMember extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'member_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'household_id',
        'first_name',
        'middle_name',
        'last_name',
        'name',
        'birth_date',
        'gender_id',
        'gender',
        'sex',
        'relationship_id',
        'relation',
        'civil_status_id',
        'civil_status',
        'is_household_head',
        'can_report_for_household',
        'is_senior',
        'is_pwd',
        'is_pregnant',
        'contact_number',
        'occupation',
        'education_level_id',
        'education_level',
        'is_graduate',
        'religion',
        'special_needs',
    ];

    protected $appends = ['vulnerable_groups', 'age'];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : null;
    }

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

    public function civilStatus(){
        return $this->belongsTo(CivilStatus::class, 'civil_status_id', 'status_id');
    }

    public function relationshipDetail()
    {
        return $this->belongsTo(Relationship::class, 'relationship_id', 'relationship_id');
    }

    public function vulnerableGroups()
    {
        return $this->hasMany(MemberVulnerableGroup::class, 'member_id', 'member_id');
    }

    public function evacuatedMembers()
    {
        return $this->hasMany(EvacuatedMember::class, 'member_id', 'member_id');
    }

    public function vulnerableGroupDetails()
    {
        return $this->belongsToMany(
            VulnerableGroup::class,
            'member_vulnerable_groups',
            'member_id',
            'vulnerable_group_id'
        );
    }

    public function relationship()
    {
        return $this->belongsTo(Relationship::class, 'relationship_id', 'relationship_id');
    }

    public function getVulnerableGroupsAttribute()
    {
        return $this->relationLoaded('vulnerableGroupDetails') 
            ? $this->vulnerableGroupDetails 
            : $this->vulnerableGroupDetails()->get();
    }
}