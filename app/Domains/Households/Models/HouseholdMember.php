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
        'birth_date',
        'gender_id',
        'relationship_id',
        'civil_status_id',
    ];

    protected $appends = ['vulnerable_groups'];

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

        static::saved(fn () => \Illuminate\Support\Facades\Cache::tags(['households'])->flush());
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::tags(['households'])->flush());
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