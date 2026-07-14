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
use App\Domains\Authentication\Models\User;
use App\Domains\Notifications\Models\DeviceToken;
use App\Domains\Notifications\Models\NotificationRecipient;
use App\Domains\AccommodationUnits\Models\UnitAllocation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Household extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql_v2';
    protected $primaryKey = 'household_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->household_id) {
                do {
                    $id = 'NHH-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('household_id', $id)->exists());

                $model->household_id = $id;
            }
        });

        static::saved(fn () => \Illuminate\Support\Facades\Cache::tags(['households'])->flush());
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::tags(['households'])->flush());
    }

    protected $fillable = [
        'household_name',
        'address_id',
        'contact_number',
        'emergency_contact',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    public function user()
    {
        return $this->hasOne(User::class, 'household_id', 'household_id');
    }
    public function members()
    {
        return $this->hasMany(HouseholdMember::class, 'household_id', 'household_id');
    }

    public function evacuations()
    {
        return $this->hasMany(EvacuationRecord::class, 'household_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id', 'address_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class, 'household_id', 'household_id');
    }

    public function notificationRecipients()
    {
        return $this->hasMany(NotificationRecipient::class, 'household_id', 'household_id');
    }

    public function currentEvacuation(){
        return $this->hasOne(EvacuationRecord::class, 'household_id')
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->whereHas('event', function ($query) {
                $query->whereNull('ended_at');
            })
            ->latest();
    }

    /**
     * All active evacuation records across ALL centers for this household.
     * Supports scattered families where members are split across multiple centers.
     */
    public function currentEvacuations(){
        return $this->hasMany(EvacuationRecord::class, 'household_id')
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->whereHas('event', function ($query) {
                $query->whereNull('ended_at');
            });
    }

    public function currentAllocation(){
        return $this->hasOneThrough(
            UnitAllocation::class,
            EvacuationRecord::class,
            'household_id',
            'evacuation_id',
            'household_id',
            'evacuation_id'
        );
    }
}